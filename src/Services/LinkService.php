<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClientRepository;
use App\Support\Logger;
use App\Support\CRest;

readonly class LinkService
{
    public function __construct(
        private B24Service       $b24Service,
        private ClientRepository $clientRepository,
        private string           $formUrl
    ) { }

    public function generateReviewLinks(int $dealId, string $domain): array
    {
        $contactIds = $this->b24Service->getDealContactIds($dealId);

        if (empty($contactIds)) return [];

        $client = $this->clientRepository->getByDomain($domain);
        if (!$client) {
            Logger::error('[LinkService] Client not found by domain', ['domain' => $domain, 'deal_id' => $dealId]);
            return [];
        }

        $link = rtrim($this->formUrl, '/') . '/' . $client['code'] . '/';

        $links = [];
        foreach ($contactIds as $contactId) {
            $encoded = $this->encodeParams($dealId, $contactId);
            $links[] = $link . $encoded . '/';
        }

        return $links;
    }

    public function sendReviewLinks(int $dealId): void
    {
        $auth = $_REQUEST['auth'] ?? null;

        $domain = $_REQUEST['DOMAIN']
            ?? (is_array($auth) ? ($auth['domain'] ?? null) : null);

        if (empty($domain)) {
            Logger::error('[LinkService] DOMAIN not found in request', ['request' => $_REQUEST]);
            http_response_code(401);
            return;
        }

        if (empty($_REQUEST['event_token'])) {
            Logger::error('[LinkService] event_token not found in request', ['request' => $_REQUEST]);
            http_response_code(400);
            return;
        }

        $reviewLinks = $this->generateReviewLinks($dealId, (string)$domain);

        $result = CRest::call('bizproc.event.send', [
            'event_token' => (string)$_REQUEST['event_token'],
            'return_values' => [
                'links' => $reviewLinks,
            ],
        ]);

        Logger::info('Responded review links', [
            'domain'  => $domain,
            'deal_id' => $dealId,
            'result'  => $result,
        ]);
    }

    private function encodeParams(int $dealId, int $contactId): string
    {
        $payload = pack('NN', $dealId, $contactId);

        $nonce = random_bytes(4);

        $key = $_ENV['VRT_ENCODE_KEY'];

        $keystream = substr(hash('sha256', $key . $nonce, true), 0, strlen($payload));
        $cipher = $payload ^ $keystream;

        $hmac = substr(hash_hmac('sha256', $nonce . $cipher, $key, true), 0, 8);

        $final = $nonce . $cipher . $hmac;

        return rtrim(strtr(base64_encode($final), '+/', '-_'), '=');
    }

    public function decodeParams(string $encoded): array
    {
        $key = $_ENV['VRT_ENCODE_KEY'];

        $data = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($data === false) {
            throw new \RuntimeException('Invalid base64 string');
        }

        $expectedLength = 4 + 8 + 8;

        if (strlen($data) !== $expectedLength) {
            throw new \RuntimeException('Invalid encoded payload length');
        }

        $nonce  = substr($data, 0, 4);
        $cipher = substr($data, 4, 8);
        $hmac   = substr($data, 12, 8);

        $calcHmac = substr(hash_hmac('sha256', $nonce . $cipher, $key, true), 0, 8);

        if (!hash_equals($hmac, $calcHmac)) {
            throw new \RuntimeException('Invalid HMAC: tampered or corrupt data');
        }

        $keystream = substr(hash('sha256', $key . $nonce, true), 0, strlen($cipher));

        $payload = $cipher ^ $keystream;

        $values = unpack('NdealId/NcontactId', $payload);

        if (!$values) {
            throw new \RuntimeException('Failed to unpack payload');
        }

        return [
            'dealId'    => $values['dealId'],
            'contactId' => $values['contactId'],
        ];
    }
}
