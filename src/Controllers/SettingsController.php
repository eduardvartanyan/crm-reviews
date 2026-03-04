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

        $domain = $_REQUEST['DOMAIN'] ?? ($_REQUEST['domain'] ?? null);
        $domainSafe = $domain ? htmlspecialchars((string)$domain) : '';

        $client = null;
        if ($domain) {
            $client = $this->clientRepository->getByDomain((string)$domain);
        }

        $clientData = $client;
        $domainView = $domainSafe;

        require __DIR__ . '/../../views/settings.php';
    }

    public function update(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $domain  = $_REQUEST['DOMAIN'] ?? ($_REQUEST['domain'] ?? null);

        $code    = mb_strtolower(trim((string)($_REQUEST['code'] ?? '')));
        $title   = trim((string)($_REQUEST['title'] ?? ''));

        $notify  = (!empty($_REQUEST['notify']) && $_REQUEST['notify'] === 'Y') ? 'Y' : 'N';
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
        if ($client && $client['domain'] !== $domain) {
            http_response_code(400);
            echo json_encode(['error' => "Код $code занят, укажите другой"]);
            return;
        }

        $this->clientRepository->updateByDomain($domain, [
            'code'      => $code,
            'title'     => $title,
            'notify'    => $notify,
            'no_repeat' => $noRepeat,
        ]);

        http_response_code(200);
        echo json_encode(['status' => 'OK']);
    }
}