<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Logger;
use Bitrix24\SDK\Services\ServiceBuilder;
use Throwable;

class B24Service
{
    public function __construct(private readonly ServiceBuilder $b24) { }

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
}
