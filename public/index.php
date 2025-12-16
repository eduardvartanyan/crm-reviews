<?php
declare(strict_types=1);

use App\Container;
use App\CRest;
use App\Logger;
use App\Services\B24Service;
use App\Services\LinkService;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    /** @var Container $container */

    switch ($uri) {
        case '/index.php':
            if ($method == 'POST') {
                $result = CRest::call('profile');
                echo '<pre>'; print_r($result); echo '</pre>';
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

                $dealReviewLinks = $linkService->getDealReviewLinks($dealId);

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
            $b24Service = $container->get(B24Service::class);
            $b24Service->getDealContactIds(172176);
            break;
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}
