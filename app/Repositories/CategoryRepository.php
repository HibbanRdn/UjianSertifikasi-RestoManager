<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function activeWithVisibleMenuCount(): array
    {
        $statement = $this->pdo->query(
            "SELECT c.id, c.name, COUNT(m.id) AS menu_count
             FROM categories c
             LEFT JOIN menu_items m ON m.category_id = c.id AND m.is_visible = 1
             WHERE c.is_active = 1
             GROUP BY c.id, c.name
             ORDER BY c.name"
        );

        return $statement->fetchAll();
    }
}
