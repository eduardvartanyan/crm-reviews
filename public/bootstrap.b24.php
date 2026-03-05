<?php
declare(strict_types=1);

/**
 * public/bootstrap.b24.php
 *
 * Инициализирует Bitrix24 SDK (bitrix24/b24phpsdk ^1.9) через placement request (auth из $_REQUEST),
 * сохраняет auth в БД (через ClientRepository->upsertAuth) и кладёт ServiceBuilder/B24Service в контейнер.
 *
 * Примечание:
 *  - Временная трассировка пишет в /_bp_bootstrap_trace.log только если включён env DEBUG_BP_BOOTSTRAP=1
 *  - Убери DEBUG_BP_BOOTSTRAP после отладки.
 */

/** @var \App\Support\Container $container */

use App\Repositories\ClientRepository;
use App\Services\B24Service;
use App\Services\LinkService;
use App\Support\CRest;
use App\Support\Logger;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Symfony\Component\HttpFoundation\Request;

// -------------------- debug trace (optional) --------------------
$traceEnabled = !empty($_ENV['DEBUG_BP_BOOTSTRAP']) && $_ENV['DEBUG_BP_BOOTSTRAP'] === '1';
$traceFile = __DIR__ . '/../_bp_bootstrap_trace.log';

$trace = static function (string $msg) use ($traceEnabled, $traceFile): void {
    if (!$traceEnabled) return;
    @file_put_contents($traceFile, date('c') . ' ' . $msg . "\n", FILE_APPEND);
};

if ($traceEnabled) {
    $trace('ENTER');
    register_shutdown_function(static function () use ($traceFile): void {
        $e = error_get_last();
        if ($e) {
            @file_put_contents(
                $traceFile,
                date('c') . ' SHUTDOWN: ' . json_encode($e, JSON_UNESCAPED_UNICODE) . "\n",
                FILE_APPEND
            );
        } else {
            @file_put_contents($traceFile, date('c') . " SHUTDOWN: OK\n", FILE_APPEND);
        }
    });

    set_error_handler(static function ($severity, $message, $file, $line) use ($traceFile) {
        @file_put_contents($traceFile, date('c') . " ERROR: $message in $file:$line\n", FILE_APPEND);
        return false;
    });
}
// ---------------------------------------------------------------

try {
    $clientRepository = $container->get(ClientRepository::class);
    $trace('STEP: got client repository');

    $auth = $_REQUEST['auth'] ?? null;

    // DOMAIN в BP-хендлере часто отсутствует, но auth.domain обычно есть
    $domain = $_REQUEST['DOMAIN'] ?? null;
    if (is_array($auth) && empty($domain) && !empty($auth['domain'])) {
        $domain = (string)$auth['domain'];
    }
    $trace('STEP: resolved domain=' . ($domain ?? 'NULL'));

    // member_id берём из auth
    $memberId = null;
    if (is_array($auth) && !empty($auth['member_id'])) {
        $memberId = (string)$auth['member_id'];
    } elseif (!empty($_REQUEST['member_id'])) {
        $memberId = (string)$_REQUEST['member_id'];
    }
    $trace('STEP: resolved memberId=' . ($memberId ?? 'NULL'));

    // контекст для CRest (чтобы refresh работал на нужный портал)
    CRest::setPortalContext($domain, $memberId);
    $trace('STEP: set portal context');

    // storage для CRest -> БД
    CRest::setSettingsStorage(
        function (?string $domain, ?string $memberId) use ($clientRepository): array {
            $client = null;

            if (!empty($memberId) && method_exists($clientRepository, 'getByMemberId')) {
                $client = $clientRepository->getByMemberId($memberId);
            }
            if (!$client && !empty($domain)) {
                $client = $clientRepository->getByDomain($domain);
            }
            if (!$client) return [];

            return [
                'domain'            => $client['domain'] ?? null,
                'member_id'         => $client['member_id'] ?? null,
                'client_endpoint'   => $client['client_endpoint'] ?? ('https://' . ($client['domain'] ?? '') . '/rest/'),
                'access_token'      => $client['access_token'] ?? null,
                'refresh_token'     => $client['refresh_token'] ?? null,
                'application_token' => $client['application_token'] ?? null,
                'token_expires_at'  => $client['token_expires_at'] ?? null,
            ];
        },
        function (array $settings, ?string $domain, ?string $memberId) use ($clientRepository): bool {
            return $clientRepository->upsertAuth(array_merge($settings, [
                'domain'     => $settings['domain'] ?? $domain,
                'member_id'  => $settings['member_id'] ?? $memberId,
            ]));
        }
    );
    $trace('STEP: set settings storage');

    // Если пришёл auth — сохраняем его
    if (is_array($auth) && !empty($auth['access_token'])) {
        $clientRepository->upsertAuth([
            'domain'            => $domain,
            'member_id'         => $memberId,
            'access_token'      => $auth['access_token'],
            'refresh_token'     => $auth['refresh_token'] ?? null,
            'expires_in'        => $auth['expires_in'] ?? null,
            'application_token' => $auth['application_token'] ?? null,
            'client_endpoint'   => $auth['client_endpoint'] ?? ($domain ? ('https://' . $domain . '/rest/') : null),
        ]);
        $trace('STEP: upserted auth from request');
    }

    // Подстраховка: если member_id пришёл, но ещё не был привязан к домену (редко, но бывает)
    if (is_array($auth) && !empty($auth['member_id']) && !empty($domain) && method_exists($clientRepository, 'getByMemberId')) {
        try {
            $clientByMember = $clientRepository->getByMemberId((string)$auth['member_id']);
            if (!$clientByMember) {
                $clientByDomain = $clientRepository->getByDomain((string)$domain);
                if ($clientByDomain && empty($clientByDomain['member_id'])) {
                    $clientRepository->upsertAuth([
                        'domain'            => (string)$domain,
                        'member_id'         => (string)$auth['member_id'],
                        'client_endpoint'   => $auth['client_endpoint'] ?? ('https://' . $domain . '/rest/'),
                        'application_token' => $auth['application_token'] ?? null,
                    ]);
                    $trace('STEP: persisted member_id binding');
                }
            }
        } catch (\Throwable $e) {
            Logger::error('[bootstrap.b24] cannot persist member_id', [
                'message' => $e->getMessage(),
                'domain' => $domain,
                'member_id' => $auth['member_id'],
            ]);
        }
    }

    // Если auth не прислали свежий токен — пробуем обновить через CRest (при необходимости)
    // Важно: CRest::call() сам обновляет при expired_token
    $settings = CRest::getSettingsForDebug();
    $trace('STEP: got settings for debug');

    if (empty($domain)) {
        $domain = $settings['domain'] ?? null;
    }
    if (empty($domain)) {
        Logger::error('[bootstrap.b24] Cannot resolve portal domain', [
            'domain' => $domain,
            'member_id' => $memberId,
        ]);
        http_response_code(401);
        exit;
    }

    // Проверим, что токен вообще есть (иначе SDK не поднимется)
    if (empty($settings['access_token']) && empty($auth['access_token'])) {
        Logger::error('[bootstrap.b24] No access_token for portal', [
            'domain' => $domain,
            'member_id' => $memberId,
        ]);
        http_response_code(401);
        exit;
    }

    // -------------------- SDK init for b24phpsdk ^1.9 --------------------
    // Создаём профиль приложения (client_id/client_secret из env)
    $clientId = $_ENV['C_REST_CLIENT_ID'] ?? '';
    $clientSecret = $_ENV['C_REST_CLIENT_SECRET'] ?? '';
    if ($clientId === '' || $clientSecret === '') {
        Logger::error('[bootstrap.b24] Missing C_REST_CLIENT_ID / C_REST_CLIENT_SECRET in env');
        http_response_code(500);
        exit;
    }

    $appProfile = ApplicationProfile::initFromArray([
        'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID'     => $clientId,
        'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => $clientSecret,
        // можно вынести в env, оставил дефолт под твои методы
        'BITRIX24_PHP_SDK_APPLICATION_SCOPE'         => $_ENV['BITRIX24_APP_SCOPE'] ?? 'crm,bizproc,im,user',
    ]);
    $trace('STEP: created ApplicationProfile');

    // --- Жёсткая подкладка placement-полей для b24phpsdk ^1.9 ---
// SDK местами читает поля из GET, местами из POST/request, поэтому кладём во все мешки.

    $placement = [];

    if (!empty($domain)) {
        $placement['DOMAIN'] = (string)$domain;
    }

    if (is_array($auth)) {
        if (!empty($auth['access_token'])) {
            $placement['AUTH_ID'] = (string)$auth['access_token'];
        }
        if (!empty($auth['refresh_token'])) {
            $placement['REFRESH_ID'] = (string)$auth['refresh_token'];
        }
        if (!empty($auth['application_token'])) {
            $placement['APP_SID'] = (string)$auth['application_token'];
        }

        if (!empty($auth['expires_in'])) {
            $placement['AUTH_EXPIRES'] = (string)$auth['expires_in'];
        } elseif (!empty($auth['expires'])) {
            $ttl = (int)$auth['expires'] - time();
            $placement['AUTH_EXPIRES'] = (string)max(0, $ttl);
        }
    }

// Важно: кладём во все суперглобалы
    foreach ($placement as $k => $v) {
        if (empty($_GET[$k])) $_GET[$k] = $v;
        if (empty($_POST[$k])) $_POST[$k] = $v;
        if (empty($_REQUEST[$k])) $_REQUEST[$k] = $v;
    }

    $trace('STEP: injected placement keys to superglobals: ' . json_encode([
            'DOMAIN' => $_GET['DOMAIN'] ?? null,
            'AUTH_ID' => isset($_GET['AUTH_ID']) ? '***' : null,
            'REFRESH_ID' => isset($_GET['REFRESH_ID']) ? '***' : null,
            'APP_SID' => isset($_GET['APP_SID']) ? '***' : null,
            'AUTH_EXPIRES' => $_GET['AUTH_EXPIRES'] ?? null,
        ], JSON_UNESCAPED_UNICODE));

    $request = Request::createFromGlobals();
    $b24 = ServiceBuilderFactory::createServiceBuilderFromPlacementRequest($request, $appProfile);

    $trace('STEP: created ServiceBuilder from placement request');
    // --------------------------------------------------------------------

    $container->set(ServiceBuilder::class, fn () => $b24);
    $container->set(B24Service::class, fn () => new B24Service($container->get(ServiceBuilder::class)));
    $container->set(LinkService::class, fn() => new LinkService(
        $container->get(ClientRepository::class),
        $_ENV['VRT_FORM_URL'] ?? '',
        $container->get(B24Service::class)
    ));

    Logger::info('[bootstrap.b24] ServiceBuilder initialized', [
        'domain' => $domain,
        'member_id' => $memberId,
    ]);

} catch (\Throwable $e) {
    Logger::error('[bootstrap.b24] exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    http_response_code(500);
    exit;
}
