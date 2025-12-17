<?php

namespace App\Controllers;

use App\Support\Container;

class ReviewController
{
    public function showForm(string $code, string $encoded, Container $container): void
    {
        http_response_code(200);
        require __DIR__ . '/../../views/review.php';
    }
}