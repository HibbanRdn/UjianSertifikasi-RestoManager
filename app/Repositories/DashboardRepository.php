<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, int> */
    public function staffSummary(): array
    {
        return [
            'received_orders' => $this->count("SELECT COUNT(*) FROM orders WHERE status = 'received'"),
            'active_visits' => $this->count("SELECT COUNT(*) FROM visits WHERE status = 'active'"),
            'occupied_tables' => $this->count("SELECT COUNT(*) FROM dining_tables WHERE status = 'occupied'"),
        ];
    }

    /** @return array<string, int> */
    public function adminSummary(): array
    {
        return [
            'active_customers' => $this->count("SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1"),
            'visible_menu' => $this->count('SELECT COUNT(*) FROM menu_items WHERE is_visible = 1'),
            'categories' => $this->count('SELECT COUNT(*) FROM categories'),
            'available_tables' => $this->count("SELECT COUNT(*) FROM dining_tables WHERE status = 'available' AND is_active = 1"),
            'occupied_tables' => $this->count("SELECT COUNT(*) FROM dining_tables WHERE status = 'occupied'"),
            'active_visits' => $this->count("SELECT COUNT(*) FROM visits WHERE status = 'active'"),
            'orders' => $this->count('SELECT COUNT(*) FROM orders'),
            'simulated_total' => $this->count("SELECT COALESCE(SUM(amount_idr),0) FROM payments WHERE status = 'paid_simulated'"),
        ];
    }

    /** @return array<string,mixed> */
    public function staffDashboard(int $days = 7): array
    {
        $days = max(1, min(31, $days));

        return [
            'summary' => $this->staffSummary(),
            'orders_by_day' => $this->dailySeries(
                "SELECT DATE(created_at) AS day, COUNT(*) AS value FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . ($days - 1) . " DAY) GROUP BY DATE(created_at)",
                $days
            ),
            'table_status' => [
                'available' => $this->count("SELECT COUNT(*) FROM dining_tables WHERE status='available' AND is_active=1"),
                'occupied' => $this->count("SELECT COUNT(*) FROM dining_tables WHERE status='occupied' AND is_active=1"),
            ],
            'oldest_waiting' => $this->pdo->query(
                "SELECT o.order_code,o.created_at,t.table_code,v.customer_name_snapshot
                 FROM orders o JOIN visits v ON v.id=o.visit_id JOIN dining_tables t ON t.id=v.table_id
                 WHERE o.status='received' ORDER BY o.created_at ASC LIMIT 5"
            )->fetchAll(),
        ];
    }

    /** @return array<string,mixed> */
    public function adminDashboard(int $days = 7): array
    {
        $days = max(1, min(31, $days));
        $offset = $days - 1;

        return [
            'summary' => $this->adminSummary(),
            'orders_by_day' => $this->dailySeries(
                "SELECT DATE(created_at) AS day, COUNT(*) AS value FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $offset DAY) GROUP BY DATE(created_at)",
                $days
            ),
            'payments_by_day' => $this->dailySeries(
                "SELECT DATE(paid_at) AS day, COALESCE(SUM(amount_idr),0) AS value FROM payments WHERE status='paid_simulated' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL $offset DAY) GROUP BY DATE(paid_at)",
                $days
            ),
            'top_items' => $this->pdo->query(
                "SELECT oi.menu_name_snapshot,SUM(oi.quantity) AS quantity_sold
                 FROM order_items oi JOIN orders o ON o.id=oi.order_id
                 WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL $offset DAY)
                 GROUP BY oi.menu_name_snapshot ORDER BY quantity_sold DESC,oi.menu_name_snapshot LIMIT 5"
            )->fetchAll(),
        ];
    }

    private function count(string $sql): int
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /** @return list<array{date:string,label:string,value:int}> */
    private function dailySeries(string $sql, int $days): array
    {
        $values = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $row) {
            $values[(string) $row['day']] = (int) $row['value'];
        }

        $series = [];
        $today = new \DateTimeImmutable('today');
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = $today->modify('-' . $offset . ' days');
            $key = $date->format('Y-m-d');
            $series[] = ['date' => $key, 'label' => $date->format('d/m'), 'value' => $values[$key] ?? 0];
        }

        return $series;
    }
}
