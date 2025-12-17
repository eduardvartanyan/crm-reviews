<?php
declare(strict_types=1);

use App\Controllers\SettingController;
use App\Repositories\ClientRepository;
use App\Services\B24Service;
use App\Services\LinkService;
use App\Support\Container;
use App\Support\Logger;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    /** @var Container $container */

    switch ($uri) {
        case '/index.php':
            if ($method === 'POST') {
                $controller = $container->get(SettingController::class);
                $controller->showForm();
            }
            break;

        case '/activities/getreviewlinks':
            if (
                $method == 'POST'
                && isset($_REQUEST['document_id']) && is_array($_REQUEST['document_id']) && count($_REQUEST['document_id']) >= 3
            ) {
                $dealId = (int) str_replace('DEAL_', '', $_REQUEST['document_id'][2]);

                $linkService = $container->get(LinkService::class);
                $b24Service  = $container->get(B24Service::class);

                $dealReviewLinks = $linkService->getDealReviewLinks($dealId, $_REQUEST['auth']['domain']);

                $url = $_REQUEST['auth']['client_endpoint'] . 'bizproc.event.send.json?' . http_build_query([
                    'auth' => $_REQUEST['auth']['access_token'],
                    'event_token' => $_REQUEST['event_token'],
                    'return_values' => [
                        'link' => $dealReviewLinks,
                    ]
                ]);
                $result = file_get_contents($url);

                Logger::info('/activities/getreviewlinks', [
                    'request'  => $_REQUEST,
                    'response' => $url,
                    'result'   => $result,
                ]);
            }
            break;

        // https://crm-reviews.ru/r/forsite/AGCLKKjyprFI09iRISNaY8GbG3nLkV1hoNiVCjMYOMMMgi6uds_h-FUsNq8/

        case '/test':
            $clientRepository = $container->get(ClientRepository::class);
            $clientId = $clientRepository->create([
                'domain'  => 'fs911.bitrix24.ru',
                'title'   => 'fs911',
                'app_sid' => '9c450b47dcbed52fc541d68617879576',
            ]);
            echo $clientId;
            break;
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}
