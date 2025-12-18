<?php

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

class System
{
    public static function getWebhook(): ?string
    {
        $pdo = Database::pdo();

        if (isset($_REQUEST['auth']['domain'])) {
            try {
                $stmt = $pdo->prepare("
                SELECT * 
                FROM clients 
                WHERE domain = :domain;
            ");
                $stmt->execute([
                    ':domain' => $_REQUEST['auth']['domain'],
                ]);

                $client = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new RuntimeException(
                    '[ClientRepository->getByDomain] Error selecting from clients -> ' . $e->getMessage()
                );
            }
        } else {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
            if (preg_match('#^/r/([^/]+)/([^/]+)/?$#', $uri, $matches)) {
                $code = $matches[1];
            } else {
                $code = $_POST['code'];
            }

            if (empty($code)) { return null; }

            try {
                $stmt = $pdo->prepare("
                SELECT * 
                FROM clients 
                WHERE code = :code;
            ");
                $stmt->execute([
                    ':code' => $code,
                ]);

                $client = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new RuntimeException(
                    '[ClientRepository->getByCode] Error selecting from clients -> ' . $e->getMessage()
                );
            }
        }

        if (empty($client)) { return null; }

        return $client['web_hook'];
    }
}