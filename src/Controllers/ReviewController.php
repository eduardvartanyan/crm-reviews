<?php

namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Services\B24Service;
use App\Services\LinkService;
use App\Services\ReviewService;

readonly class ReviewController
{
    public function __construct(
        private LinkService $linkService,
        private ReviewService $reviewService
    ) { }

    public function showForm(string $code, string $encoded): void
    {
        http_response_code(200);
        require __DIR__ . '/../../views/review.php';
    }

    public function submit(): void
    {
        $decoded = $this->linkService->decodeParams($_REQUEST['encoded']);

        $clientCode = $_REQUEST['code'] ?? '';
        $contactId  = (int) $decoded['contactId'];
        $dealId     = (int) $decoded['dealId'];
        $rating     = isset($_REQUEST['rating']) ? (int) $_REQUEST['rating'] : null;
        $comment    = trim((string) ($_REQUEST['review'] ?? ''));

        $this->reviewService->submitReview($clientCode, $contactId, $dealId, $rating, $comment);

        http_response_code(200);
        require __DIR__ . '/../../views/reviewsubmit.php';
    }
}
