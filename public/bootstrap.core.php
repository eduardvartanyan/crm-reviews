<?php
declare(strict_types=1);

use App\Controllers\ReviewController;
use App\Controllers\SettingsController;
use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Services\B24Service;
use App\Services\LinkService;
use App\Services\ReviewService;
use App\Support\Container;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = new Container();

$container->set(ClientRepository::class,   fn() => new ClientRepository());
$container->set(ReviewRepository::class,   fn() => new ReviewRepository());
$container->set(SettingsController::class, fn() => new SettingsController($container->get(ClientRepository::class)));

// ВАЖНО: фабрика (closure) выполнится только когда сервис реально запросят,
// поэтому B24Service может быть установлен позже (в bootstrap.b24.php) — это нормально.
if (empty($_ENV['VRT_FORM_URL'])) {
    throw new \RuntimeException('VRT_FORM_URL is not set in .env');
}

$container->set(LinkService::class, fn() => new LinkService(
    $container->get(ClientRepository::class),
    $_ENV['VRT_FORM_URL'] ?? ''
));

$container->set(ReviewService::class, fn() => new ReviewService(
    $container->get(ReviewRepository::class),
    $container->get(ClientRepository::class),
    $container->get(B24Service::class)
));

$container->set(ReviewController::class, fn() => new ReviewController(
    $container->get(LinkService::class),
    $container->get(ClientRepository::class),
    $container->get(ReviewRepository::class),
    null
));