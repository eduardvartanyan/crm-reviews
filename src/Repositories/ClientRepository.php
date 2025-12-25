<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;
use PDOException;
use RuntimeException;

class ClientRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * @return int — id добавленного клиента
     */
    public function create(array $values): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO clients (domain, code, title, app_sid) 
                VALUES (:domain, :code, :title, :app_sid);
            ");
            $stmt->execute([
                ':domain'  => $values['domain'],
                ':code'    => $values['code'],
                ':title'   => $values['code'],
                ':app_sid' => $values['app_sid'],
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException(
                '[ClientRepository->create] Error inserting into clients -> ' . $e->getMessage()
            );
        }
    }

    public function getByDomain(string $domain): ?array
    {
        if ($domain === '') return null;

        try {
            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM clients 
                WHERE domain = :domain;
            ");
            $stmt->execute([
                ':domain' => $domain,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) return null;

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                '[ClientRepository->getByDomain] Error selecting from clients -> ' . $e->getMessage()
            );
        }
    }

    public function getByCode(string $code): ?array
    {
        if ($code === '') return null;

        try {
            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM clients 
                WHERE code = :code;
            ");
            $stmt->execute([
                ':code' => $code,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) return null;

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                '[ClientRepository->getByCode] Error selecting from clients -> ' . $e->getMessage()
            );
        }
    }

    /**
     * @param string $domain
     * @param array{
     *      code: string,
     *      title: string,
     *      web_hook: string,
     *      notify: 'Y'|'N'
     *  } $values
     * @return bool
     */
    public function updateByDomain(string $domain, array $values): bool
    {
        if ($domain === '') return false;

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM clients
            WHERE domain = :domain
            LIMIT 1;
        ");

        if ($stmt->execute([':domain' => $domain]) === false) return false;

        if ($client = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $stmt = $this->pdo->prepare("
                UPDATE clients
                SET code = :code, title = :title, web_hook = :web_hook, notify = :notify
                WHERE domain = :domain
            ");
            $stmt->execute([
                ':code'     => $values['code'] ?? $client['code'],
                ':title'    => $values['title'] ?? $client['title'],
                ':web_hook' => $values['web_hook'] ?? $client['web_hook'],
                ':notify'   => $values['notify'] ?? $client['notify'],
                ':domain'   => $domain,
            ]);

            return true;
        }

        return false;
    }
}