<?php

namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;
use App\Services\B24Service;
use App\Services\LinkService;

readonly class ReviewController
{
    public function __construct(
        private LinkService $linkService,
        private ReviewRepository $reviewRepository,
        private ClientRepository $clientRepository,
        private B24Service $b24Service,
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

        $contactId = (int) $decoded['contactId'];
        $dealId    = (int) $decoded['dealId'];

        $client = $this->clientRepository->getByCode($clientCode);

        $this->reviewRepository->create([
            'clientId'  => (int) $client['id'],
            'contactId' => $contactId,
            'dealId'    => $dealId,
            'rating'    => $ratingValue,
            'comment'   => $reviewValue,
        ]);

        $dealTitle = $this->b24Service->getDealTitleById($dealId);
        $this->b24Service->addCommentToContact($contactId, "Добавлен отзыв по сделке <a href='/crm/deal/details/$dealId/'>$dealTitle</a>.
Оценка: $ratingValue
Комментарий: $reviewValue");

        $contactName = $this->b24Service->getContactTitleById($contactId);
        $this->b24Service->addCommentToDeal($dealId, "<a href='/crm/contact/details/$contactId/'>$contactName</a> добавил отзыв.
Оценка: $ratingValue
Комментарий: $reviewValue");

        http_response_code(200);
        require __DIR__ . '/../../views/reviewsubmit.php';
    }
}
