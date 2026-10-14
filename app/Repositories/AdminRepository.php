<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AdminRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function categories(): array
    {
        return $this->pdo->query('SELECT c.*,COUNT(m.id) AS menu_count FROM categories c LEFT JOIN menu_items m ON m.category_id=c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
    }

    public function menuItems(): array
    {
        return $this->pdo->query('SELECT m.*,c.name AS category_name FROM menu_items m JOIN categories c ON c.id=m.category_id ORDER BY c.name,m.name')->fetchAll();
    }

    public function tables(): array
    {
        return $this->pdo->query("SELECT t.*,SUM(CASE WHEN v.status='active' THEN 1 ELSE 0 END) AS active_visits,COUNT(v.id) AS total_visits FROM dining_tables t LEFT JOIN visits v ON v.table_id=t.id GROUP BY t.id ORDER BY t.table_code")->fetchAll();
    }

    public function staff(): array
    {
        return $this->pdo->query("SELECT id,name,email,phone,is_active,created_at FROM users WHERE role='staff' ORDER BY name")->fetchAll();
    }

    /** @return array<string,int> */
    public function administrationCounts(): array
    {
        return [
            'categories' => (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'menu_items' => (int) $this->pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn(),
            'tables' => (int) $this->pdo->query('SELECT COUNT(*) FROM dining_tables')->fetchColumn(),
            'staff' => (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn(),
        ];
    }

    public function report(string $from, string $to): array
    {
        $params = ['from' => $from . ' 00:00:00', 'to' => $to . ' 23:59:59'];
        $summary = $this->pdo->prepare(
            "SELECT COUNT(o.id) AS order_count,COALESCE(SUM(o.total_idr),0) AS simulated_total,
                    SUM(CASE WHEN p.method='qris_sim' THEN 1 ELSE 0 END) AS qris_count,
                    SUM(CASE WHEN p.method='cash_sim' THEN 1 ELSE 0 END) AS cash_count,
                    COUNT(DISTINCT o.visit_id) AS visit_count
             FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.created_at BETWEEN :from AND :to"
        );
        $summary->execute($params);
        $orders = $this->pdo->prepare(
            "SELECT o.id,o.order_code,o.status,o.total_idr,o.created_at,v.visit_code,v.customer_name_snapshot,t.table_code,p.method,p.status AS payment_status
             FROM orders o JOIN visits v ON v.id=o.visit_id JOIN dining_tables t ON t.id=v.table_id JOIN payments p ON p.order_id=o.id
             WHERE o.created_at BETWEEN :from AND :to ORDER BY o.created_at DESC,o.id DESC"
        );
        $orders->execute($params);
        $tableUsage = $this->pdo->prepare(
            "SELECT t.table_code,COUNT(v.id) AS visit_count FROM dining_tables t LEFT JOIN visits v ON v.table_id=t.id AND v.started_at BETWEEN :from AND :to GROUP BY t.id,t.table_code ORDER BY visit_count DESC,t.table_code"
        );
        $tableUsage->execute($params);
        $topItems = $this->pdo->prepare(
            "SELECT oi.menu_name_snapshot, SUM(oi.quantity) AS quantity_sold,
                    SUM(oi.line_total_idr) AS simulated_total
             FROM order_items oi JOIN orders o ON o.id=oi.order_id
             WHERE o.created_at BETWEEN :from AND :to
             GROUP BY oi.menu_name_snapshot
             ORDER BY quantity_sold DESC, simulated_total DESC, oi.menu_name_snapshot"
        );
        $topItems->execute($params);

        return [
            'summary' => $summary->fetch(),
            'orders' => $orders->fetchAll(),
            'table_usage' => $tableUsage->fetchAll(),
            'top_items' => $topItems->fetchAll(),
        ];
    }
}
