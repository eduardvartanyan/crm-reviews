<?php
declare(strict_types=1);

use App\Services\B24Service;
use App\Services\LinkService;
use App\Support\Container;
use App\Support\Database;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = new Container();
$container->set(B24Service::class,     fn() => new B24Service($container->get(ServiceBuilder::class)));
$container->set(ServiceBuilder::class, fn() => ServiceBuilderFactory::createServiceBuilderFromWebhook($_ENV['B24_WEBHOOK_CODE']));
$container->set(LinkService::class,    fn() => new LinkService($container->get(B24Service::class)));
$container->set(Database::class,       fn() => new Database([
    'host' => $_ENV['DB_HOST'],
    'port' => $_ENV['DB_PORT'],
    'dbname' => $_ENV['DB_NAME'],
    'user' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
]));
