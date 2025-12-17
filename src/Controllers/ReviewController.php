<?php

namespace App\Controllers;

class ReviewController
{
    public function showForm(string $code, string $encoded): void
    {
        http_response_code(200);
        require __DIR__ . '/../../views/review.php';
    }
}