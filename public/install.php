<?php
declare(strict_types=1);

use App\Repositories\ClientRepository;
use App\Support\Container;
use App\Support\Logger;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/bootstrap.core.php';

/** @var Container $container */
$clientRepository = $container->get(ClientRepository::class);

// 1) Серверный колл от Bitrix после BX24.installFinish()
if (($_REQUEST['event'] ?? null) === 'ONAPPINSTALL' && !empty($_REQUEST['auth']) && is_array($_REQUEST['auth'])) {

    $auth = $_REQUEST['auth'];
    $domain = $auth['domain'] ?? ($_REQUEST['DOMAIN'] ?? null);

    try {
        // гарантируем, что клиент есть
        if ($domain && !($clientRepository->getByDomain($domain))) {
            $code = explode('.', $domain)[0] ?? $domain;
            $clientRepository->create([
                'domain'  => $domain,
                'code'    => $code,
                'app_sid' => $auth['application_token'] ?? ($_REQUEST['APP_SID'] ?? '-'),
            ]);
        }

        // сохраняем OAuth в БД
        $clientRepository->upsertAuth([
            'domain'            => $auth['domain'] ?? null,
            'member_id'         => $auth['member_id'] ?? null,
            'access_token'      => $auth['access_token'] ?? null,
            'refresh_token'     => $auth['refresh_token'] ?? null,
            'expires_in'        => $auth['expires_in'] ?? null,
            'application_token' => $auth['application_token'] ?? null,
            'client_endpoint'   => $auth['client_endpoint'] ?? ( $domain ? ('https://' . $domain . '/rest/') : null ),
        ]);

        Logger::info('[install] ONAPPINSTALL saved auth', [
            'domain' => $domain,
            'member_id' => $auth['member_id'] ?? null,
        ]);

        http_response_code(200);
        echo 'OK';
        exit;

    } catch (Throwable $e) {
        Logger::error('[install] ONAPPINSTALL error', [
            'message' => $e->getMessage(),
        ]);
        http_response_code(500);
        echo 'ERROR';
        exit;
    }
}

// 2) Iframe-страница установки (первый заход)
$domain = $_REQUEST['DOMAIN'] ?? '';
$appSid = $_REQUEST['APP_SID'] ?? '';

try {
    if ($domain !== '' && $appSid !== '') {
        $existing = $clientRepository->getByDomain($domain);

        if (!$existing) {
            $code = explode('.', $domain)[0] ?? $domain;

            $clientRepository->create([
                'domain'  => $domain,
                'code'    => $code,
                'app_sid' => $appSid,
            ]);

            Logger::info('[install] client created on iframe open', [
                'domain' => $domain,
                'app_sid' => $appSid,
            ]);
        }
    }
} catch (Throwable $e) {
    Logger::error('[install] iframe create client error', [
        'domain' => $domain,
        'message' => $e->getMessage(),
    ]);
}

Logger::info('[install] iframe open', [
    'request' => $_REQUEST,
]);

http_response_code(200);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <script src="//api.bitrix24.com/api/v1/"></script>
</head>
<body>
Устанавливаем приложение…

<script>
    BX24.init(function () {

        // 1) ставим действие бизнес-процесса
        BX24.callMethod('user.current', {}, function(current_user) {
            if(current_user.error()) {
                alert("Error: " + current_user.error());
                return;
            }

            BX24.callMethod(
                'bizproc.activity.add',
                {
                    'CODE': 'getReviewLink',
                    'HANDLER': 'https://crm-reviews.ru/activities/getreviewlinks',
                    'AUTH_USER_ID': current_user.data().ID,
                    'USE_SUBSCRIPTION': 'Y',
                    'NAME': { 'ru': 'Ссылка на отзыв' },
                    'DESCRIPTION': { 'ru': 'Действие генерирует и возвращает ссылки на отзывы для всех контактов сделки' },
                    'PROPERTIES': {},
                    'RETURN_PROPERTIES': {
                        'links': {
                            'Name': { 'ru': 'Ссылки на отзывы' },
                            'Type': 'string',
                            'Multiple': 'Y',
                            'Default': null
                        }
                    },
                    'DOCUMENT_TYPE': ['crm', 'CCrmDocumentDeal', 'DEAL'],
                    'FILTER': { INCLUDE: [ ['crm','CCrmDocumentDeal'] ] }
                },
                function(result) {
                    if(result.error()) {
                        alert("Error: " + result.error());
                    } else {
                        // 2) обязательно сообщаем Bitrix, что установка завершена
                        BX24.installFinish();
                    }
                }
            );
        });

    });
</script>

</body>
</html>