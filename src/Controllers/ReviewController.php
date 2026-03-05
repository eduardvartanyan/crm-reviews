<?php

namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Services\LinkService;
use App\Services\ReviewService;

readonly class ReviewController
{
    public function __construct(
        private LinkService $linkService,
        private ClientRepository $clientRepository,
        private ReviewRepository $reviewRepository,
        private ?ReviewService $reviewService = null
    ) {}

    public function showForm(string $code, string $encoded): void
    {
        $decoded = $this->linkService->decodeParams($encoded);

        $contactId = (int)($decoded['contactId'] ?? 0);
        $dealId    = (int)($decoded['dealId'] ?? 0);

        if ($contactId <= 0 || $dealId <= 0 || $code === '') {
            http_response_code(400);
            echo 'ERROR';
            return;
        }

        $client = $this->clientRepository->getByCode($code);
        if (!$client) {
            http_response_code(404);
            echo 'ERROR';
            return;
        }

        // По умолчанию считаем, что форму показывать можно
        $canShow = true;

        // Публичная проверка без Bitrix: запрет повторного отзыва
        $noRepeat = ($client['no_repeat'] ?? 'Y') === 'Y';
        if ($noRepeat && $this->reviewRepository->hasReview($contactId, $dealId)) {
            $canShow = false;
        }

        // Если ReviewService доступен (например, в контексте портала) — можно использовать его логику,
        // но НЕ обязуемся к ней в публичном контексте.
        if ($this->reviewService !== null) {
            $canShow = $this->reviewService->canShowReviewForm($contactId, $dealId, $code);
        }

        http_response_code(200);

        if ($canShow) {
            require __DIR__ . '/../../views/review.php';
        } else {
            require __DIR__ . '/../../views/reviewsubmit.php';
        }
    }

    public function submit(): void
    {
        if ($this->reviewService === null) {
            throw new \RuntimeException('ReviewService is not initialized (portal bootstrap missing)');
        }

        $decoded = $this->linkService->decodeParams($_REQUEST['encoded'] ?? '');

        $clientCode = (string)($_REQUEST['code'] ?? '');
        $contactId  = (int)($decoded['contactId'] ?? 0);
        $dealId     = (int)($decoded['dealId'] ?? 0);
        $rating     = isset($_REQUEST['rating']) ? (int)$_REQUEST['rating'] : null;
        $comment    = trim((string)($_REQUEST['review'] ?? ''));

        $this->reviewService->submitReview($clientCode, $contactId, $dealId, $rating, $comment);

        http_response_code(200);
        require __DIR__ . '/../../views/reviewsubmit.php';
    }
}
