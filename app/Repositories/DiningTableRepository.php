<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DiningTableRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function available(): array
    {
        $statement = $this->pdo->query(
            "SELECT id, table_code, status FROM dining_tables WHERE is_active = 1 AND status = 'available' ORDER BY table_code"
        );

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findActiveById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, table_code, status FROM dining_tables WHERE id = :id AND is_active = 1 LIMIT 1');
        $statement->execute(['id' => $id]);
        $table = $statement->fetch();

        return is_array($table) ? $table : null;
    }

    public function isAvailable(int $id): bool
    {
        $table = $this->findActiveById($id);

        return $table !== null && $table['status'] === 'available';
    }
}
