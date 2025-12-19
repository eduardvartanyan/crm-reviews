<?php

namespace App\Support;

use PDO;

final class System
{
    public static function getWebhook(): ?string
    {
        $identifier = self::resolveClientIdentifier();
        if (!$identifier) {
            return null;
        }

        $client = self::findClient($identifier);
        return $client['web_hook'] ?? null;
    }

    public static function resolveClientIdentifier(): ?array
    {
        if (!empty($_REQUEST['auth']['domain'])) {
            return [
                'type' => 'domain',
                'value' => $_REQUEST['auth']['domain'],
            ];
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        if (preg_match('#^/r/([^/]+)/#', $uri, $m)) {
            return [
                'type' => 'code',
                'value' => $m[1],
            ];
        }

        if (!empty($_POST['code'])) {
            return [
                'type' => 'code',
                'value' => $_POST['code'],
            ];
        }

        return null;
    }

    public static function findClient(array $identifier): ?array
    {
        $pdo = Database::pdo();

        if ($identifier['type'] === 'domain') {
            $stmt = $pdo->prepare('
                SELECT * 
                FROM clients 
                WHERE domain = :value 
                LIMIT 1
            ');
        } else {
            $stmt = $pdo->prepare('
                SELECT * 
                FROM clients 
                WHERE code = :value 
                LIMIT 1
          ');
        }

        $stmt->execute(['value' => $identifier['value']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}