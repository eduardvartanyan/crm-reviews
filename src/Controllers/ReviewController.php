<?php

namespace App\Controllers;

use App\Services\LinkService;
use App\Support\Logger;
use Throwable;

readonly class ReviewController
{
    public function __construct(private LinkService $linkService) { }

    public function showForm(string $code, string $encoded): void
    {
        http_response_code(200);
        require __DIR__ . '/../../views/review.php';
    }

    public function submit(): void
    {
        $code        = $_REQUEST['code'] ?? '';
        $encoded     = $_REQUEST['encoded'] ?? '';
        $ratingValue = isset($_REQUEST['rating']) ? (int) $_REQUEST['rating'] : null;
        $reviewValue = trim((string) ($_REQUEST['review'] ?? ''));

        $decoded = $this->linkService->decodeParams($encoded);

        try {
            Logger::info('Review submitted', [
                'clientCode' => $code,
                'contactId'  => $decoded['contactId'],
                'dealId'     => $decoded['dealId'],
                'rating'     => $ratingValue,
                'review'     => $reviewValue,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable $e) {
            Logger::error('Review log failed: ' . $e->getMessage());
        }

        http_response_code(200);
        require __DIR__ . '/../../views/reviewsubmit.php';
    }
}
