<?php

namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Services\LinkService;

readonly class ReviewController
{
    public function __construct(
        private LinkService $linkService,
        private ReviewRepository $reviewRepository,
        private ClientRepository $clientRepository,
    ) { }

    public function showForm(string $code, string $encoded): void
    {
        http_response_code(200);
        require __DIR__ . '/../../views/review.php';
    }

    public function submit(): void
    {
        $clientCode  = $_REQUEST['code'] ?? '';
        $encoded     = $_REQUEST['encoded'] ?? '';
        $ratingValue = isset($_REQUEST['rating']) ? (int) $_REQUEST['rating'] : null;
        $reviewValue = trim((string) ($_REQUEST['review'] ?? ''));

        $decoded = $this->linkService->decodeParams($encoded);

        $client = $this->clientRepository->getByCode($clientCode);

        $this->reviewRepository->create([
            'clientId'  => (int) $client['id'],
            'contactId' => (int) $decoded['contactId'],
            'dealId'    => (int) $decoded['dealId'],
            'rating'    => (int) $ratingValue,
            'comment'   => trim($reviewValue),
        ]);

        http_response_code(200);
        require __DIR__ . '/../../views/reviewsubmit.php';
    }
}
