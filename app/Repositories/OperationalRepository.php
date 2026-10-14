<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OperationalRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string,mixed>> */
    public function receivedOrders(): array
    {
        $orders = $this->pdo->query(
            "SELECT o.id,o.order_code,o.total_idr,o.created_at,v.id AS visit_id,v.customer_name_snapshot,
                    t.table_code,p.method,p.status AS payment_status
             FROM orders o JOIN visits v ON v.id=o.visit_id JOIN dining_tables t ON t.id=v.table_id
             JOIN payments p ON p.order_id=o.id WHERE o.status='received'
             ORDER BY o.created_at ASC,o.id ASC"
        )->fetchAll();

        return $this->attachItems($orders);
    }

    /** @return list<array<string,mixed>> */
    public function activeVisits(): array
    {
        $visits = $this->pdo->query(
            "SELECT v.id,v.visit_code,v.customer_name_snapshot,v.started_at,t.table_code,
                    COUNT(o.id) AS order_count,
                    SUM(CASE WHEN o.status='received' THEN 1 ELSE 0 END) AS received_count,
                    SUM(CASE WHEN o.status='served' THEN 1 ELSE 0 END) AS served_count,
                    SUM(CASE WHEN p.status!='paid_simulated' OR p.id IS NULL THEN 1 ELSE 0 END) AS unpaid_count
             FROM visits v JOIN dining_tables t ON t.id=v.table_id
             LEFT JOIN orders o ON o.visit_id=v.id LEFT JOIN payments p ON p.order_id=o.id
             WHERE v.status='active' GROUP BY v.id,v.visit_code,v.customer_name_snapshot,v.started_at,t.table_code
             ORDER BY v.started_at ASC,v.id ASC"
        )->fetchAll();

        $orders = $this->pdo->query(
            "SELECT o.id,o.order_code,o.visit_id,o.status,o.total_idr,o.created_at
             FROM orders o JOIN visits v ON v.id=o.visit_id WHERE v.status='active' ORDER BY o.created_at,o.id"
        )->fetchAll();
        $byVisit = [];
        foreach ($orders as $order) {
            $byVisit[(int) $order['visit_id']][] = $order;
        }
        foreach ($visits as &$visit) {
            $visit['orders'] = $byVisit[(int) $visit['id']] ?? [];
            $visit['can_close'] = (int) $visit['order_count'] > 0 && (int) $visit['received_count'] === 0 && (int) $visit['unpaid_count'] === 0;
        }
        unset($visit);

        return $visits;
    }

    /** @param list<array<string,mixed>> $orders @return list<array<string,mixed>> */
    private function attachItems(array $orders): array
    {
        if ($orders === []) {
            return [];
        }
        $ids = array_map(static fn (array $order): int => (int) $order['id'], $orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->pdo->prepare("SELECT order_id,menu_name_snapshot,quantity FROM order_items WHERE order_id IN ($placeholders) ORDER BY id");
        $statement->execute($ids);
        $itemsByOrder = [];
        foreach ($statement->fetchAll() as $item) {
            $itemsByOrder[(int) $item['order_id']][] = $item;
        }
        foreach ($orders as &$order) {
            $order['items'] = $itemsByOrder[(int) $order['id']] ?? [];
        }
        unset($order);

        return $orders;
    }
}
