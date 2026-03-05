<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Logger;
use Bitrix24\SDK\Services\ServiceBuilder;
use Throwable;

readonly class B24Service
{
    public function __construct(private ServiceBuilder $b24) { }

    public function getDealContactIds(int $dealId): array
    {
        $ids = [];

        // 1) Привязки контактов (если есть)
        try {
            $result = $this->b24->getCRMScope()->dealContact()->itemsGet($dealId);
            foreach ($result->getDealContacts() as $item) {
                $cid = (int)($item->CONTACT_ID ?? 0);
                if ($cid > 0) $ids[] = $cid;
            }
        } catch (Throwable $e) {
            Logger::error('Ошибка при получении привязанных контактов сделки', [
                'deal_id' => $dealId,
                'message' => $e->getMessage(),
            ]);
        }

        // 2) Fallback: основной контакт CONTACT_ID в самой сделке
        // (на некоторых порталах/сценариях только он и заполнен)
        if (empty($ids)) {
            try {
                // В SDK v1.9 у CRM deal обычно есть метод get()
                $deal = $this->b24->getCRMScope()->deal()->get($dealId);

                // В разных версиях SDK поле может лежать по-разному
                $contactId = 0;

                if (method_exists($deal, 'getContactId')) {
                    $contactId = (int)$deal->getContactId();
                } elseif (property_exists($deal, 'CONTACT_ID')) {
                    $contactId = (int)$deal->CONTACT_ID;
                } elseif (method_exists($deal, 'getDeal')) {
                    $d = $deal->getDeal();
                    if (is_object($d) && property_exists($d, 'CONTACT_ID')) {
                        $contactId = (int)$d->CONTACT_ID;
                    }
                }

                if ($contactId > 0) $ids[] = $contactId;

            } catch (Throwable $e) {
                Logger::error('Ошибка при получении CONTACT_ID сделки', [
                    'deal_id' => $dealId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Уникализируем и возвращаем
        $ids = array_values(array_unique(array_filter($ids, fn($v) => (int)$v > 0)));

        Logger::info('[BP] resolved deal contact ids', [
            'deal_id' => $dealId,
            'count' => count($ids),
            'ids' => $ids,
        ]);

        return $ids;
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     assigned_by: int
     * }|null
     */
    public function getDealById(int $id): ?array
    {
        try {
            foreach ($this->b24->getCRMScope()->deal()->list(
                [],
                ['ID' => $id],
                ['TITLE', 'ASSIGNED_BY_ID']
            )->getDeals() as $deal) {
                return [
                    'id'          => $id,
                    'title'       => $deal->TITLE,
                    'assigned_by' => $deal->ASSIGNED_BY_ID,
                ];
            }
        } catch (Throwable $e) {
            Logger::error('Ошибка при получении ID контакта', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
                'data'    => $id
            ]);
        }

        return null;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     assigned_by: int
     * }|null
     */
    public function getContactById(int $id): ?array
    {
        try {
            foreach($this->b24->getCRMScope()->contact()->list(
                [],
                ['ID' => $id],
                ['NAME', 'ASSIGNED_BY_ID'],
                0
            )->getContacts() as $contact) {
                return [
                    'id'          => $id,
                    'name'        => $contact->NAME,
                    'assigned_by' => $contact->ASSIGNED_BY_ID,
                ];
            }
        } catch (Throwable $e) {
            Logger::error('Ошибка при получении ID контакта', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
                'data'    => $id
            ]);
        }

        return null;
    }

    public function addCommentToContact(int $id, string $text): void
    {
        try {
            $result = $this->b24->getCRMScope()->timelineComment()->add([
                'ENTITY_ID' => $id,
                'ENTITY_TYPE' => 'contact',
                'COMMENT' => $text,
            ]);
            Logger::info('Added comment to contact', [
                'id'         => $result->getId(),
                'contact_id' => $id,
                'message'    => $text,
            ]);
        } catch (Throwable $e) {
            Logger::error('Error adding comment to contact', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
                'data'    => [
                    'contact_id' => $id,
                    'message' => $text,
                ]
            ]);
        }
    }

    public function addCommentToDeal(int $id, string $text): void
    {
        try {
            $result = $this->b24->getCRMScope()->timelineComment()->add([
                'ENTITY_ID' => $id,
                'ENTITY_TYPE' => 'deal',
                'COMMENT' => $text,
            ]);
            Logger::info('Added comment to deal', [
                'id'      => $result->getId(),
                'deal_id' => $id,
                'message' => $text,
            ]);
        } catch (Throwable $e) {
            Logger::error('Error adding comment to deal', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
                'data'    => [
                    'deal_id' => $id,
                    'message' => $text,
                ]
            ]);
        }
    }

    public function notify(int $userId, string $text): void
    {
        try {
            $result = $this->b24->getIMScope()->notify()->fromSystem(
                $userId,
                $text,
            );
            Logger::info('Notified', [
                'id'      => $result->getId(),
                'user_id' => $userId,
                'message' => $text,
            ]);
        } catch (Throwable $e) {
            Logger::error('Error notifying', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
                'data'    => [
                    'user_id' => $userId,
                    'message' => $text,
                ]
            ]);
        }
    }
}
