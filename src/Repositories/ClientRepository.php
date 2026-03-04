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

    public function getByMemberId(string $memberId): ?array
    {
        if ($memberId === '') return null;

        try {
            $stmt = $this->pdo->prepare("
            SELECT *
            FROM clients
            WHERE member_id = :member_id
            LIMIT 1;
        ");
            $stmt->execute([':member_id' => $memberId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                '[ClientRepository->getByMemberId] Error selecting from clients -> ' . $e->getMessage()
            );
        }
    }

    /**
     * @param string $domain
     * @param array{
     *      code: string,
     *      title: string,
     *      web_hook: string,
     *      notify: 'Y'|'N',
     *      no_repeat: 'Y'|'N',
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
                SET code = :code, title = :title, notify = :notify, no_repeat = :no_repeat
                WHERE domain = :domain
            ");
            $stmt->execute([
                ':code'      => $values['code'] ?? $client['code'],
                ':title'     => $values['title'] ?? $client['title'],
                ':notify'    => $values['notify'] ?? $client['notify'],
                ':no_repeat' => $values['no_repeat'] ?? $client['no_repeat'],
                ':domain'    => $domain,
            ]);

            return true;
        }

        return false;
    }


    public function upsertAuth(array $values): bool
    {
        $domain = $values['domain'] ?? '';
        $memberId = $values['member_id'] ?? '';

        if ($domain === '' && $memberId === '') return false;

        $client = null;
        if ($memberId !== '') {
            $client = $this->getByMemberId($memberId);
        }
        if (!$client && $domain !== '') {
            $client = $this->getByDomain($domain);
        }

        $expiresAt = null;
        if (!empty($values['expires_in'])) {
            $expiresAt = (new \DateTimeImmutable('now'))
                ->modify('+' . (int)$values['expires_in'] . ' seconds')
                ->format('Y-m-d H:i:s');
        } elseif (!empty($values['token_expires_at'])) {
            $expiresAt = $values['token_expires_at'];
        } elseif ($client && !empty($client['token_expires_at'])) {
            $expiresAt = $client['token_expires_at'];
        }

        $payload = [
            ':member_id'         => $values['member_id'] ?? ($client['member_id'] ?? null),
            ':domain'            => $values['domain'] ?? ($client['domain'] ?? null),
            ':application_token' => $values['application_token'] ?? ($client['application_token'] ?? null),
            ':client_endpoint'   => $values['client_endpoint'] ?? ($client['client_endpoint'] ?? null),
            ':access_token'      => $values['access_token'] ?? ($client['access_token'] ?? null),
            ':refresh_token'     => $values['refresh_token'] ?? ($client['refresh_token'] ?? null),
            ':token_expires_at'  => $expiresAt,
        ];

        try {
            if ($client) {
                $stmt = $this->pdo->prepare("
                UPDATE clients
                SET
                    member_id = COALESCE(:member_id, member_id),
                    domain = COALESCE(:domain, domain),
                    application_token = COALESCE(:application_token, application_token),
                    client_endpoint = COALESCE(:client_endpoint, client_endpoint),
                    access_token = COALESCE(:access_token, access_token),
                    refresh_token = COALESCE(:refresh_token, refresh_token),
                    token_expires_at = COALESCE(:token_expires_at::timestamp, token_expires_at)
                WHERE id = :id
            ");
                $payload[':id'] = $client['id'];
                $stmt->execute($payload);
                return true;
            }

            if ($domain === '') return false;

            $code = explode('.', $domain)[0] ?? $domain;

            $stmt = $this->pdo->prepare("
            INSERT INTO clients (domain, code, title, app_sid, member_id, application_token, client_endpoint, access_token, refresh_token, token_expires_at)
            VALUES (:domain, :code, :title, :app_sid, :member_id, :application_token, :client_endpoint, :access_token, :refresh_token, :token_expires_at::timestamp)
        ");

            $stmt->execute([
                ':domain'            => $domain,
                ':code'              => $code,
                ':title'             => $code,
                ':app_sid'           => $values['application_token'] ?? ($values['app_sid'] ?? '-'),
                ':member_id'         => $memberId ?: null,
                ':application_token' => $values['application_token'] ?? null,
                ':client_endpoint'   => $values['client_endpoint'] ?? ('https://' . $domain . '/rest/'),
                ':access_token'      => $values['access_token'] ?? null,
                ':refresh_token'     => $values['refresh_token'] ?? null,
                ':token_expires_at'  => $expiresAt,
            ]);

            return true;
        } catch (PDOException $e) {
            throw new RuntimeException(
                '[ClientRepository->upsertAuth] Error upserting clients auth -> ' . $e->getMessage()
            );
        }
    }
}
