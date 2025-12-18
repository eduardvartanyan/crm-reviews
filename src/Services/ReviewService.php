<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClientRepository;
use App\Repositories\ReviewRepository;

readonly class ReviewService
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private ClientRepository $clientRepository,
        private B24Service       $b24Service
    ) { }

    public function submitReview(
        string $clientCode,
        int    $contactId,
        int    $dealId,
        int    $rating,
        string $comment
    ): void
    {
        $client = $this->clientRepository->getByCode($clientCode);

        $this->reviewRepository->create([
            'clientId'  => (int) $client['id'],
            'contactId' => $contactId,
            'dealId'    => $dealId,
            'rating'    => $rating,
            'comment'   => $comment,
        ]);

        $dealTitle = $this->b24Service->getDealTitleById($dealId);
        $this->b24Service->addCommentToContact($contactId, "Добавлен отзыв по сделке <a href='/crm/deal/details/$dealId/'>$dealTitle</a>.
Оценка: $rating
Комментарий: $comment");

        $contactName = $this->b24Service->getContactTitleById($contactId);
        $this->b24Service->addCommentToDeal($dealId, "<a href='/crm/contact/details/$contactId/'>$contactName</a> добавил отзыв.
Оценка: $rating
Комментарий: $comment");
    }
}