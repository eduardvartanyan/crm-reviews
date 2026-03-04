<?php
declare(strict_types=1);

use App\Controllers\LinkController;
use App\Controllers\ReviewController;
use App\Controllers\SettingsController;
use App\Support\Container;

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
                require_once __DIR__ . '/../public/bootstrap.b24.php';
                $linkController = $container->get(LinkController::class);
                $linkController->sendReviewLinks();
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
    echo $e->getMessage();
}
