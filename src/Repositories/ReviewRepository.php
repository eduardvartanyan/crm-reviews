<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;
use PDOException;
use RuntimeException;

class ReviewRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * @return int — id добавленного отзыва
     */
    public function create(array $values): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO reviews (client_id, contact_id, deal_id, rating, comment) 
                VALUES (:client_id, :contact_id, :deal_id, :rating, :comment);
            ");
            $stmt->execute([
                ':client_id'  => $values['clientId'],
                ':contact_id' => $values['contactId'],
                ':deal_id'    => $values['dealId'],
                ':rating'     => $values['rating'],
                ':comment'    => $values['comment'],
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException(
                '[ReviewRepository->create] Error inserting into reviews -> ' . $e->getMessage()
            );
        }
    }

    public function hasReview(int $contactId, int $dealId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM reviews
                WHERE contact_id = :contact_id
                  AND deal_id = :deal_id;
            ");
            $stmt->execute([
                ':contact_id' => $contactId,
                ':deal_id'    => $dealId,
            ]);

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException(
                '[ReviewRepository->list] Error selecting from reviews -> ' . $e->getMessage()
            );
        }
    }
}
