<?php
declare(strict_types=1);

use App\Support\CRest;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/bootstrap.core.php';
require_once __DIR__ . '/../public/bootstrap.b24.php';

CRest::checkServer();
