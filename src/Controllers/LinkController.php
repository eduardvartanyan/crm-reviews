<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Services\LinkService;
use App\Support\Logger;

readonly class LinkController
{
    public function __construct(
        private LinkService $linkService,
        private ClientRepository $clientRepository,
    ) { }

    public function sendReviewLinks(): void
    {
        Logger::info('[BP] getreviewlinks hit', [
            'has_auth' => isset($_REQUEST['auth']),
            'domain_top' => $_REQUEST['DOMAIN'] ?? null,
            'domain_auth' => $_REQUEST['auth']['domain'] ?? null,
            'member_id' => $_REQUEST['auth']['member_id'] ?? null,
            'has_event_token' => !empty($_REQUEST['event_token']),
            'document_id' => $_REQUEST['document_id'] ?? null,
        ]);

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
            Logger::error('[BP] unauthorized: missing auth fields', [
                'has_auth' => is_array($auth),
            ]);
            http_response_code(401);
            return;
        }

        $memberId = $auth['member_id'] ?? null;
        $domain = $auth['domain'] ?? ($_REQUEST['DOMAIN'] ?? null);
        $appToken = $auth['application_token'] ?? null;

        if (empty($appToken) || (empty($memberId) && empty($domain))) {
            Logger::error('[BP] unauthorized: missing auth fields', [
                'domain' => $domain,
                'member_id' => $memberId,
                'has_auth' => is_array($auth),
            ]);
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
            Logger::error('[BP] forbidden: application_token mismatch', [
                'member_id' => $memberId,
                'domain' => $domain,
                'client_token' => $client['application_token'] ?? null,
                'request_token' => $appToken,
            ]);
            http_response_code(403);
            return;
        }

        $dealId = (int) str_replace('DEAL_', '', $_REQUEST['document_id'][2]);
        $this->linkService->sendReviewLinks($dealId);
    }
}