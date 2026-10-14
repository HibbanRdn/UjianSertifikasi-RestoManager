<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\DashboardRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class DashboardAnalyticsTest extends TestCase
{
    private PDO $pdo;
    private int $userId;
    private int $tableId;
    private int $visitId;
    private int $orderId;

    protected function setUp(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $this->pdo = $app['pdo'];
        $suffix = bin2hex(random_bytes(5));
        $this->pdo->prepare("INSERT INTO users(name,email,phone,password_hash,role,is_active) VALUES('Analytics Test',:email,'0812',:hash,'customer',1)")->execute(['email' => "analytics-$suffix@example.test", 'hash' => password_hash('Analytics123!', PASSWORD_DEFAULT)]);
        $this->userId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO dining_tables(table_code,status,is_active) VALUES(:code,'occupied',1)")->execute(['code' => 'AN-' . strtoupper($suffix)]);
        $this->tableId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO visits(visit_code,user_id,table_id,customer_name_snapshot,status) VALUES(:code,:user,:table_id,'Analytics Test','active')")->execute(['code' => 'V-' . strtoupper($suffix), 'user' => $this->userId, 'table_id' => $this->tableId]);
        $this->visitId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO orders(order_code,visit_id,idempotency_key,status,total_idr,estimated_minutes_min,estimated_minutes_max) VALUES(:code,:visit,:key,'received',25000,15,25)")->execute(['code' => 'O-' . strtoupper($suffix), 'visit' => $this->visitId, 'key' => hash('sha256', $suffix)]);
        $this->orderId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO order_items(order_id,menu_item_id,menu_name_snapshot,price_idr_snapshot,quantity,line_total_idr) VALUES(:order,1,'Analytics Menu',25000,1,25000)")->execute(['order' => $this->orderId]);
        $this->pdo->prepare("INSERT INTO payments(order_id,method,status,amount_idr,paid_at) VALUES(:order,'qris_sim','paid_simulated',25000,NOW())")->execute(['order' => $this->orderId]);
    }

    protected function tearDown(): void
    {
        if (!isset($this->orderId)) {
            return;
        }
        $this->pdo->prepare('DELETE FROM payments WHERE order_id=:id')->execute(['id' => $this->orderId]);
        $this->pdo->prepare('DELETE FROM order_items WHERE order_id=:id')->execute(['id' => $this->orderId]);
        $this->pdo->prepare('DELETE FROM orders WHERE id=:id')->execute(['id' => $this->orderId]);
        $this->pdo->prepare('DELETE FROM visits WHERE id=:id')->execute(['id' => $this->visitId]);
        $this->pdo->prepare('DELETE FROM dining_tables WHERE id=:id')->execute(['id' => $this->tableId]);
        $this->pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $this->userId]);
    }

    public function testDashboardsReturnChronologicalRealDatabaseAnalytics(): void
    {
        $repository = new DashboardRepository($this->pdo);
        $staff = $repository->staffDashboard();
        $admin = $repository->adminDashboard();

        self::assertCount(7, $staff['orders_by_day']);
        self::assertCount(7, $admin['payments_by_day']);
        self::assertSame(date('Y-m-d'), $staff['orders_by_day'][6]['date']);
        self::assertGreaterThanOrEqual(1, $staff['orders_by_day'][6]['value']);
        self::assertGreaterThanOrEqual(25000, $admin['payments_by_day'][6]['value']);
        self::assertGreaterThanOrEqual(1, $staff['table_status']['occupied']);
        self::assertContains('Analytics Menu', array_column($admin['top_items'], 'menu_name_snapshot'));
        self::assertContains('Analytics Test', array_column($staff['oldest_waiting'], 'customer_name_snapshot'));
    }
}
