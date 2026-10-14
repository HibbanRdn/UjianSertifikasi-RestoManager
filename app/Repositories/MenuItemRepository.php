<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MenuItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function publicCatalog(?int $categoryId = null, ?int $limit = null, ?string $search = null): array
    {
        $sql = "SELECT m.id, m.name, m.description, m.price_idr, m.image_path,
                       m.is_available, c.id AS category_id, c.name AS category_name
                FROM menu_items m
                INNER JOIN categories c ON c.id = m.category_id
                WHERE c.is_active = 1 AND m.is_visible = 1";
        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND c.id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $normalizedSearch = trim((string) $search);
        if ($normalizedSearch !== '') {
            $sql .= ' AND (m.name LIKE :search_name OR m.description LIKE :search_description OR c.name LIKE :search_category)';
            $pattern = '%' . $normalizedSearch . '%';
            $params['search_name'] = $pattern;
            $params['search_description'] = $pattern;
            $params['search_category'] = $pattern;
        }

        $sql .= ' ORDER BY c.name, m.name';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min($limit, 24));
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findVisibleById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT m.id, m.name, m.description, m.price_idr, m.image_path, m.is_available,
                    c.id AS category_id, c.name AS category_name
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.id = :id AND m.is_visible = 1 AND c.is_active = 1
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $item = $statement->fetch();

        return is_array($item) ? $item : null;
    }

    /** @param list<int> $ids @return list<array<string, mixed>> */
    public function findVisibleByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->pdo->prepare(
            "SELECT m.id, m.name, m.description, m.price_idr, m.image_path, m.is_available,
                    c.id AS category_id, c.name AS category_name
             FROM menu_items m
             INNER JOIN categories c ON c.id = m.category_id
             WHERE m.id IN ($placeholders) AND m.is_visible = 1 AND c.is_active = 1
             ORDER BY m.name"
        );
        $statement->execute($ids);

        return $statement->fetchAll();
    }
}
