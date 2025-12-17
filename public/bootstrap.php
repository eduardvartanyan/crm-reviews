<?php
declare(strict_types=1);

use App\Controllers\ReviewController;
use App\Controllers\SettingsController;
use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Services\B24Service;
use App\Services\LinkService;
use App\Support\Container;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = new Container();

$container->set(B24Service::class,         fn() => new B24Service($container->get(ServiceBuilder::class)));
$container->set(ServiceBuilder::class,     fn() => ServiceBuilderFactory::createServiceBuilderFromWebhook($_ENV['B24_WEBHOOK_CODE']));
$container->set(LinkService::class,        fn() => new LinkService(
    $container->get(B24Service::class),
    $container->get(ClientRepository::class),
    $_ENV['VRT_FORM_URL']
));
$container->set(ClientRepository::class,   fn() => new ClientRepository());
$container->set(ReviewRepository::class,   fn() => new ReviewRepository());
$container->set(SettingsController::class, fn() => new SettingsController($container->get(ClientRepository::class)));
$container->set(ReviewController::class,   fn() => new ReviewController(
    $container->get(LinkService::class),
    $container->get(ReviewRepository::class),
    $container->get(ClientRepository::class)
));
