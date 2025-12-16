<?php
declare(strict_types=1);

namespace App\Services;

use Exception;

class LinkService
{
    public function __construct(private readonly B24Service $b24Service) { }

    public function getDealReviewLinks(int $dealId): ?array
    {
        $contactIds = $this->b24Service->getDealContactIds($dealId);

        $clientId = $_ENV['C_REST_CLIENT_ID'];
        $link = $_ENV['VRT_FORM_URL'] . $_ENV['VRT_VENDOR_NAME'] . '/';

        $links = [];
        foreach ($contactIds as $contactId) {
            $encoded = $this->encodeParams($clientId, $dealId, $contactId);
            $links[] = $link . $encoded . '/';
        }

        return $links;
    }

    private function encodeParams(string $clientId, int $dealId, int $contactId): string
    {
        $clientLen = strlen($clientId);

        $payload  = chr($clientLen) . $clientId;
        $payload .= pack('NN', $dealId, $contactId);

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
        $encoded = strtr($encoded, '-_', '+/');
        $encoded .= str_repeat('=', 4 - strlen($encoded) % 4);
        $raw = base64_decode($encoded);

        $nonce = substr($raw, 0, 4);
        $hmac  = substr($raw, -8);
        $cipher = substr($raw, 4, -8);

        $key = $_ENV['VRT_ENCODE_KEY'];

        $calcHmac = substr(hash_hmac('sha256', $nonce . $cipher, $key, true), 0, 8);
        if (!hash_equals($hmac, $calcHmac)) {
            throw new Exception("Invalid signature");
        }

        $keystream = substr(hash('sha256', $key . $nonce, true), 0, strlen($cipher));
        $payload = $cipher ^ $keystream;

        $clientLen = ord($payload[0]);
        $clientId = substr($payload, 1, $clientLen);

        $rest = substr($payload, 1 + $clientLen);
        $arr = unpack('Ndeal/Ncontact', $rest);

        return [
            'clientId' => $clientId,
            'dealId' => $arr['deal'],
            'contactId' => $arr['contact'],
        ];
    }
}
