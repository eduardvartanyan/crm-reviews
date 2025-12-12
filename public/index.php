<?php
declare(strict_types=1);

use App\CRest;

require __DIR__ . '/../vendor/autoload.php';

$result = CRest::call('profile');

echo '<pre>';
print_r($result);
echo '</pre>';
