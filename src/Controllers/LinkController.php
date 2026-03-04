<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Services\LinkService;

readonly class LinkController
{
    public function __construct(
        private LinkService $linkService,
        private ClientRepository $clientRepository,
    ) { }

    public function sendReviewLinks(): void
    {
        if (
            empty($_REQUEST['document_id'])
            || !is_array($_REQUEST['document_id'])
            || count($_REQUEST['document_id']) < 3
        ) {
            http_response_code(400);
            return;
        }

        $auth = $_REQUEST['auth'] ?? null;
        if (!is_array($auth)) {
            http_response_code(401);
            return;
        }

        $memberId = $auth['member_id'] ?? null;
        $domain = $auth['domain'] ?? ($_REQUEST['DOMAIN'] ?? null);
        $appToken = $auth['application_token'] ?? null;

        if (empty($appToken) || (empty($memberId) && empty($domain))) {
            http_response_code(401);
            return;
        }

        $client = null;
        if (!empty($memberId)) {
            $client = $this->clientRepository->getByMemberId((string)$memberId);
        }
        if (!$client && !empty($domain)) {
            $client = $this->clientRepository->getByDomain((string)$domain);
        }

        if (!$client || empty($client['application_token']) || !hash_equals((string)$client['application_token'], (string)$appToken)) {
            http_response_code(403);
            return;
        }

        $dealId = (int) str_replace('DEAL_', '', $_REQUEST['document_id'][2]);
        $this->linkService->sendReviewLinks($dealId);
    }
}