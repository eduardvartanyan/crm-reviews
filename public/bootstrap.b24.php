<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;
use App\Services\B24Service;
use App\Support\CRest;
use App\Support\Logger;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\AccessToken;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;

/** @var \App\Support\Container $container */

$clientRepository = $container->get(ClientRepository::class);

$domain = $_REQUEST['DOMAIN'] ?? null;

$auth = $_REQUEST['auth'] ?? null;
$memberId = null;

if (is_array($auth) && !empty($auth['member_id'])) {
    $memberId = (string)$auth['member_id'];
} elseif (!empty($_REQUEST['member_id'])) {
    $memberId = (string)$_REQUEST['member_id'];
}

CRest::setPortalContext($domain, $memberId);

CRest::setSettingsStorage(
    function (?string $domain, ?string $memberId) use ($clientRepository): array {
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
    function (array $settings, ?string $domain, ?string $memberId) use ($clientRepository): bool {
        return $clientRepository->upsertAuth(array_merge($settings, [
            'domain' => $settings['domain'] ?? $domain,
            'member_id' => $settings['member_id'] ?? $memberId,
        ]));
    }
);

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
}

$settings = CRest::getSettingsForDebug();

$hasFreshTokenInRequest = is_array($auth) && !empty($auth['access_token']);

if (!$hasFreshTokenInRequest && !empty($settings['token_expires_at'])) {
    try {
        $expiresAt = new \DateTimeImmutable((string)$settings['token_expires_at']);
        $now = new \DateTimeImmutable('now');

        if ($expiresAt <= $now->modify('+60 seconds')) {
            CRest::call('scope', []);
            $settings = CRest::getSettingsForDebug();
        }
    } catch (\Throwable $e) {
        Logger::error('[bootstrap.b24] token_expires_at parse error', ['e' => $e->getMessage()]);
    }
}

$portal = $settings['domain'] ?? $domain;
if (empty($portal)) {
    Logger::error('[bootstrap.b24] Cannot resolve portal domain', ['domain' => $domain, 'member_id' => $memberId]);
    http_response_code(401);
    exit;
}

$accessToken = $settings['access_token'] ?? null;
if (empty($accessToken)) {
    Logger::error('[bootstrap.b24] No access_token for portal', ['domain' => $portal, 'member_id' => $memberId]);
    http_response_code(401);
    exit;
}

$credentials = new Credentials(
    new AccessToken($accessToken),
    'https://' . $portal
);

if (method_exists(ServiceBuilderFactory::class, 'createServiceBuilderFromCredentials')) {
    $b24 = ServiceBuilderFactory::createServiceBuilderFromCredentials($credentials);
} else {
    // fallback: напрямую
    $b24 = new ServiceBuilder($credentials);
}

$container->set(ServiceBuilder::class, fn() => $b24);
$container->set(B24Service::class, fn() => new B24Service($container->get(ServiceBuilder::class)));

Logger::info('[bootstrap.b24] ServiceBuilder initialized', [
    'domain' => $portal,
    'member_id' => $memberId,
]);
