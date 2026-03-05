<?php
declare(strict_types=1);

use App\Controllers\LinkController;
use App\Controllers\ReviewController;
use App\Controllers\SettingsController;
use App\Support\Container;
use App\Support\Logger;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/bootstrap.core.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    /** @var Container $container */

    if (
        $method === 'GET'
        && preg_match('#^/r/([^/]+)/([^/]+)/?$#', $uri, $matches)
    ) {
        $controller = $container->get(ReviewController::class);
        $controller->showForm($matches[1], $matches[2]);

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
            if ($method === 'POST') {
                Logger::info('[BP] hit /activities/getreviewlinks', [
                    'has_auth' => isset($_REQUEST['auth']),
                    'member_id' => $_REQUEST['auth']['member_id'] ?? null,
                    'domain' => $_REQUEST['auth']['domain'] ?? ($_REQUEST['DOMAIN'] ?? null),
                    'has_event_token' => !empty($_REQUEST['event_token']),
                ]);

                Logger::info('[BP] before bootstrap.b24');
                require_once __DIR__ . '/../public/bootstrap.b24.php';
                Logger::info('[BP] after bootstrap.b24');

                $linkController = $container->get(LinkController::class);
                Logger::info('[BP] before LinkController::sendReviewLinks');
                $linkController->sendReviewLinks();
                Logger::info('[BP] after LinkController::sendReviewLinks');
            }
            break;

        case '/app-settings/update':
            if ($method === 'POST') {
                $controller = $container->get(SettingsController::class);
                $controller->update();
            }
            break;

        case '/review/submit':
            if ($method === 'POST') {
                $controller = $container->get(ReviewController::class);
                $controller->submit();
            }
            break;

        case '/test':
            phpinfo();
            break;
    }
} catch (Throwable $e) {
    Logger::error('[index] exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    http_response_code(500);
    echo 'ERROR';
}
