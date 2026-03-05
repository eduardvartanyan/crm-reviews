<?php
declare(strict_types=1);

use App\Controllers\ReviewController;
use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Services\B24Service;
use App\Services\ReviewService;
use App\Support\CRest;
use App\Support\Logger;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\AuthToken;


/** @var \App\Support\Container $container */

try {
    $clientRepository = $container->get(ClientRepository::class);

    $code = (string)($_REQUEST['code'] ?? '');
    if ($code === '') {
        throw new \RuntimeException('[bootstrap.portal] Missing code');
    }

    $client = $clientRepository->getByCode($code);
    if (!$client) {
        throw new \RuntimeException('[bootstrap.portal] Client not found by code: ' . $code);
    }

    $domain   = (string)($client['domain'] ?? '');
    $memberId = (string)($client['member_id'] ?? '');

    if ($domain === '') {
        throw new \RuntimeException('[bootstrap.portal] Client has empty domain');
    }

    // Важно: задаём контекст портала для CRest (в т.ч. для refresh по storage)
    CRest::setPortalContext($domain, $memberId ?: null);

    // Подключаем хранилище токенов из БД (как у тебя в bootstrap.b24.php)
    CRest::setSettingsStorage(
        function (?string $domainArg, ?string $memberIdArg) use ($clientRepository, $domain, $memberId): array {
            // Сабмит идёт по коду -> мы уже знаем домен/мембер, поэтому можно целиться точно
            $client = null;

            if (!empty($memberId)) {
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
        function (array $settings, ?string $domainArg, ?string $memberIdArg) use ($clientRepository, $domain, $memberId): bool {
            return $clientRepository->upsertAuth(array_merge($settings, [
                'domain' => $settings['domain'] ?? $domainArg ?? $domain,
                'member_id' => $settings['member_id'] ?? $memberIdArg ?? $memberId,
            ]));
        }
    );

    // Берём актуальные токены из storage (CRest при необходимости сам обновит при вызове)
    $settings = CRest::getSettingsForDebug();

    $accessToken  = (string)($settings['access_token'] ?? '');
    $refreshToken = (string)($settings['refresh_token'] ?? '');
    $appSid       = (string)($settings['application_token'] ?? $client['app_sid'] ?? '');

    if ($accessToken === '' || $refreshToken === '' || $appSid === '') {
        throw new \RuntimeException('[bootstrap.portal] Missing tokens in DB for domain=' . $domain);
    }

    /**
     * В SDK v1.9 самый совместимый способ — создать ServiceBuilder из placement request.
     * Поэтому "подкладываем" ожидаемые поля в superglobals.
     */
    $_GET['DOMAIN']       = $domain;
    $_GET['AUTH_ID']      = $accessToken;
    $_GET['REFRESH_ID']   = $refreshToken;
    $_GET['APP_SID']      = $appSid;
    $_GET['AUTH_EXPIRES'] = '3600';

    // На всякий случай — чтобы SDK/ваш код видел эти значения и в REQUEST
    $_REQUEST['DOMAIN']       = $_REQUEST['DOMAIN'] ?? $domain;
    $_REQUEST['AUTH_ID']      = $_REQUEST['AUTH_ID'] ?? $accessToken;
    $_REQUEST['REFRESH_ID']   = $_REQUEST['REFRESH_ID'] ?? $refreshToken;
    $_REQUEST['APP_SID']      = $_REQUEST['APP_SID'] ?? $appSid;
    $_REQUEST['AUTH_EXPIRES'] = $_REQUEST['AUTH_EXPIRES'] ?? '3600';

    $authToken = AuthToken::initFromArray([
        'access_token'  => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_in'    => 3600,
        'domain'        => $domain,
        'member_id'     => $memberId ?: null,
    ]);

    $credentials = new Credentials(
        $authToken,
        'https://' . $domain
    );

    if (method_exists(ServiceBuilderFactory::class, 'createServiceBuilderFromCredentials')) {
        $b24 = ServiceBuilderFactory::createServiceBuilderFromCredentials($credentials);
    } else {
        $b24 = new \Bitrix24\SDK\Services\ServiceBuilder($credentials);
    }

    $container->set(ServiceBuilder::class, fn() => $b24);
    $container->set(B24Service::class, fn() => new B24Service($container->get(ServiceBuilder::class)));
    $container->set(ReviewService::class, fn() => new ReviewService(
        $container->get(ReviewRepository::class),
        $container->get(ClientRepository::class),
        $container->get(B24Service::class)
    ));

    // Переопределяем ReviewController: теперь он уже с ReviewService
    $container->set(ReviewController::class, fn() => new ReviewController(
        $container->get(\App\Services\LinkService::class),
        $container->get(ClientRepository::class),
        $container->get(ReviewRepository::class),
        $container->get(ReviewService::class)
    ));

    Logger::info('[bootstrap.portal] initialized', [
        'domain' => $domain,
        'member_id' => $memberId,
        'code' => $code,
    ]);

} catch (\Throwable $e) {
    Logger::error('[bootstrap.portal] exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    http_response_code(500);
    echo 'ERROR';
    exit;
}
