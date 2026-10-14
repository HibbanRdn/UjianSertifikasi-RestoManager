<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class VisitRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findActiveByUserId(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT v.id, v.visit_code, v.user_id, v.table_id, v.status, t.table_code
             FROM visits v
             INNER JOIN dining_tables t ON t.id = v.table_id
             WHERE v.user_id = :user_id AND v.status = 'active'
             LIMIT 1"
        );
        $statement->execute(['user_id' => $userId]);
        $visit = $statement->fetch();

        return is_array($visit) ? $visit : null;
    }
}
