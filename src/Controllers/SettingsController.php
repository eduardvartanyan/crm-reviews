<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Support\Logger;

class SettingsController
{
    public function __construct(private readonly ClientRepository $clientRepository) { }

    public function showForm(): void
    {
        http_response_code(200);
        require __DIR__ . '/../../views/settings.php';
    }

    public function update(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $domain  = $_REQUEST['domain'] ?? null;
        $code    = mb_strtolower(trim($_REQUEST['code'])) ?? '';
        $title   = trim($_REQUEST['title']) ?? '';
        $webhook = trim($_REQUEST['webhook']) ?? '';
        $notify = (!empty($_REQUEST['notify']) && $_REQUEST['notify'] === 'Y') ? 'Y' : 'N';
        $noRepeat = (!empty($_REQUEST['no_repeat']) && $_REQUEST['no_repeat'] === 'Y') ? 'Y' : 'N';

        if (!$domain) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing DOMAIN']);
            return;
        }

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Заполните название компании']);
            return;
        }

        if ($code === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Заполните код для ссылки']);
            return;
        }

        $client = $this->clientRepository->getByCode($code);
        if (isset($client) && $client['domain'] !== $domain) {
            http_response_code(400);
            echo json_encode(['error' => "Код $code занят, укажите другой"]);
            return;
        }

        if ($webhook === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Заполните ссылку на вебхук']);
            return;
        }

        $this->clientRepository->updateByDomain($domain, [
            'code'      => $code,
            'title'     => $title,
            'web_hook'  => $webhook,
            'notify'    => $notify,
            'no_repeat' => $noRepeat,
        ]);

        http_response_code(200);
        echo json_encode(['status' => 'OK']);
    }
}