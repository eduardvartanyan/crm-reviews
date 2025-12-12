<?php
declare(strict_types=1);

use App\CRest;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {

    switch ($uri) {
        case '/':
            if ($method == 'GET') {
                $result = CRest::call('profile');
                echo '<pre>'; print_r($result); echo '</pre>';
            }
            break;

        case '/activities/getreviewlink':
            if ($method == 'GET') {
                echo 'Get review link';
            }
            break;
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}
