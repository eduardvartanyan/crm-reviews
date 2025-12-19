<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ClientRepository;

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
        $code    = trim($_REQUEST['code'] ?? '');
        $title   = trim($_REQUEST['title'] ?? '');
        $webhook = trim($_REQUEST['webhook'] ?? '');

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

        if ($webhook === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Заполните ссылку на вебхук']);
            return;
        }

        $this->clientRepository->updateCodeByDomain($domain, $code);
        $this->clientRepository->updateTitleByDomain($domain, $title);
        $this->clientRepository->updateWebhookByDomain($domain, $webhook);

        http_response_code(200);
        echo json_encode(['status' => 'OK']);
    }
}