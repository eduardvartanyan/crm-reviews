<?php
declare(strict_types=1);

use App\Container;
use App\Database\Database;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/bootstrap.php';

/** @var Database $dbService
  * @var Container $container */

try {
    $db = $container->get(Database::class)->getConnection();
    $sql = "
        CREATE TABLE IF NOT EXISTS clients (
            id SERIAL PRIMARY KEY,
            domain VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            app_sid VARCHAR(255) NOT NULL,
            active VARCHAR(1) NOT NULL DEFAULT 'Y',
            created_at TIMESTAMP DEFAULT NOW()
        );
    ";

    $db->exec($sql);

    echo "Table 'clients' created successfully.\n";
} catch (ReflectionException $e) {
    echo $e->getMessage();
}
