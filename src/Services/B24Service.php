<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Logger;
use Bitrix24\SDK\Services\ServiceBuilder;
use Throwable;

readonly class B24Service
{
    public function __construct(private ServiceBuilder $b24) { }

    public function getDealContactIds(int $id): ?array
    {
        try {
            $result = $this->b24->getCRMScope()->dealContact()->itemsGet($id);
            $contactIds = [];
            foreach ($result->getDealContacts() as $item) {
                $contactIds[] = $item->CONTACT_ID;
            }
            return $contactIds;
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

    public function getDealTitleById(int $id): ?string
    {
        try {
            $result = $this->b24->getCRMScope()->deal()->get($id);

            return $result->deal()->TITLE;
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

    public function getContactTitleById(int $id): ?string
    {
        try {
            $result = $this->b24->getCRMScope()->contact()->get($id);

            return $result->contact()->NAME;
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
}
