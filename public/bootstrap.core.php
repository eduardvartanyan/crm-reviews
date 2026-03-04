<?php
declare(strict_types=1);

use App\Controllers\SettingsController;
use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Support\Container;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = new Container();

$container->set(ClientRepository::class,   fn() => new ClientRepository());
$container->set(ReviewRepository::class,   fn() => new ReviewRepository());
$container->set(SettingsController::class, fn() => new SettingsController($container->get(ClientRepository::class)));
