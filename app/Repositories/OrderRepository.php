<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function listOwnedByStatus(int $userId, string $status): array
    {
        if (!in_array($status, ['received', 'served'], true)) {
            throw new \InvalidArgumentException('Status order tidak didukung.');
        }

        $statement = $this->pdo->prepare(
            "SELECT o.id, o.order_code, o.status, o.total_idr, o.created_at, o.served_at,
                    v.visit_code, v.status AS visit_status, dt.table_code,
                    p.method, p.status AS payment_status
             FROM orders o
             INNER JOIN visits v ON v.id = o.visit_id
             INNER JOIN dining_tables dt ON dt.id = v.table_id
             INNER JOIN payments p ON p.order_id = o.id
             WHERE v.user_id = :user_id AND o.status = :status
             ORDER BY o.created_at DESC, o.id DESC"
        );
        $statement->execute(['user_id' => $userId, 'status' => $status]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function receiptOwnedBy(int $orderId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT o.id, o.order_code, o.status, o.total_idr, o.estimated_minutes_min, o.estimated_minutes_max,
                    o.created_at, o.served_at, v.visit_code, v.status AS visit_status,
                    v.customer_name_snapshot, dt.table_code, p.method, p.status AS payment_status, p.paid_at
             FROM orders o
             INNER JOIN visits v ON v.id = o.visit_id
             INNER JOIN dining_tables dt ON dt.id = v.table_id
             INNER JOIN payments p ON p.order_id = o.id
             WHERE o.id = :order_id AND v.user_id = :user_id
             LIMIT 1"
        );
        $statement->execute(['order_id' => $orderId, 'user_id' => $userId]);
        $order = $statement->fetch();
        if (!is_array($order)) {
            return null;
        }

        $items = $this->pdo->prepare(
            'SELECT menu_name_snapshot, price_idr_snapshot, quantity, line_total_idr
             FROM order_items WHERE order_id = :order_id ORDER BY id'
        );
        $items->execute(['order_id' => $orderId]);
        $order['items'] = $items->fetchAll();

        return $order;
    }
}
