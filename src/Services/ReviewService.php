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


        $dealAssignedBy = 0;
        if ($deal = $this->b24Service->getDealById($dealId)) {
            $anchor = $deal['title'];
            $text = "Добавлен отзыв по сделке <a href='/crm/deal/details/$dealId/'>$anchor</a>.
Оценка: $rating
Комментарий: $comment";
            $this->b24Service->addCommentToContact($contactId, $text);

            if ($client['notify'] === 'Y') {
                $this->b24Service->notify($deal['assigned_by'], $text);
                $dealAssignedBy = $deal['assigned_by'];
            }
        }

        if ($contact = $this->b24Service->getContactById($contactId)) {
            $anchor = $contact['name'];
            $text = "<a href='/crm/contact/details/$contactId/'>$anchor</a> добавил отзыв.
Оценка: $rating
Комментарий: $comment";
            $this->b24Service->addCommentToDeal($dealId, $text);

            if ($dealAssignedBy > 0 && $dealAssignedBy !== $contact['assigned_by']) {
                $this->b24Service->notify($contact['assigned_by'], $text);
            }
        }
    }

    public function canShowReviewForm(int $contactId, int $dealId, string $code): bool
    {
        $client = $this->clientRepository->getByCode($code);

        if ($this->isRepeatReviewDisabled($client)) {
            return !$this->reviewRepository->hasReview($contactId, $dealId);
        }

        return true;
    }

    private function isRepeatReviewDisabled(array $client): bool
    {
        return $client['no_repeat'] === 'Y';
    }
}
