<?php
declare(strict_types=1);

use App\Container;
use App\Services\B24Service;
use Bitrix24\SDK\Services\ServiceBuilder;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = new Container();
$container->set(B24Service::class,     fn() => new B24Service($container->get(ServiceBuilder::class)));
$container->set(ServiceBuilder::class, fn() => ServiceBuilderFactory::createServiceBuilderFromWebhook($_ENV['B24_WEBHOOK_CODE']));
