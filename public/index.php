<?php
declare(strict_types=1);

use App\Controllers\ReviewController;
use App\Controllers\SettingsController;
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

    // https://crm-reviews.ru/r/forsait/dtglOIcwpapZYDHJMZ9uQH4lZ7k/
    if ($method === 'GET' && preg_match('#^/r/([^/]+)/([^/]+)/?$#', $uri, $matches)) {

        $code    = $matches[1];
        $encoded = $matches[2];

        $controller = $container->get(ReviewController::class);
        $controller->showForm($code, $encoded);

        exit;
    }

    switch ($uri) {
        case '/index.php':
            if ($method === 'POST') {
                $controller = $container->get(SettingsController::class);
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

        case '/app-settings/update':
            if ($method === 'POST') {
                $controller = $container->get(SettingsController::class);
                $controller->update();
            }
            break;

        case '/test':
            phpinfo();
            break;
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}
