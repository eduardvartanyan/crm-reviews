<?php
declare(strict_types=1);

namespace App\Controllers;

class SettingController
{
    public function showForm(): void
    {
        http_response_code(200);
        require __DIR__ . '/../../views/settings.php';
    }
}