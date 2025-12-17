<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClientRepository;
use Exception;

readonly class LinkService
{
    public function __construct(
        private B24Service       $b24Service,
        private ClientRepository $clientRepository,
        private string           $formUrl
    ) { }

    public function getDealReviewLinks(int $dealId, string $domain): ?array
    {
        $contactIds = $this->b24Service->getDealContactIds($dealId);
        $client = $this->clientRepository->getByDomain($domain);

        $link = $this->formUrl . $client['title'] . '/';

        $links = [];
        foreach ($contactIds as $contactId) {
            $encoded = $this->encodeParams($dealId, $contactId);
            $links[] = $link . $encoded . '/';
        }

        return $links;
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

    private function decodeParams(string $encoded): array
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
