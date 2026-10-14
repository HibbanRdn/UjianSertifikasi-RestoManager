<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exceptions\CheckoutException;
use App\Services\OperationalService;
use PDO;
use PHPUnit\Framework\TestCase;

final class OperationalFlowTest extends TestCase
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
        $email = 'ops-' . bin2hex(random_bytes(5)) . '@example.test';
        $user = $this->pdo->prepare("INSERT INTO users(name,email,phone,password_hash,role,is_active) VALUES('Ops Test',:email,'08123',:hash,'customer',1)");
        $user->execute(['email' => $email, 'hash' => password_hash('OpsTest123!', PASSWORD_DEFAULT)]);
        $this->userId = (int) $this->pdo->lastInsertId();
        $this->tableId = (int) $this->pdo->query("SELECT id FROM dining_tables WHERE status='available' ORDER BY id DESC LIMIT 1")->fetchColumn();
        $this->pdo->prepare("UPDATE dining_tables SET status='occupied' WHERE id=:id")->execute(['id' => $this->tableId]);
        $visit = $this->pdo->prepare("INSERT INTO visits(visit_code,user_id,table_id,customer_name_snapshot,status) VALUES(:code,:user_id,:table_id,'Ops Test','active')");
        $visit->execute(['code' => 'VIS-TEST-' . bin2hex(random_bytes(4)), 'user_id' => $this->userId, 'table_id' => $this->tableId]);
        $this->visitId = (int) $this->pdo->lastInsertId();
        $order = $this->pdo->prepare("INSERT INTO orders(order_code,visit_id,idempotency_key,status,total_idr) VALUES(:code,:visit_id,:key,'received',28000)");
        $order->execute(['code' => 'ORD-TEST-' . bin2hex(random_bytes(4)), 'visit_id' => $this->visitId, 'key' => bin2hex(random_bytes(32))]);
        $this->orderId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO order_items(order_id,menu_item_id,menu_name_snapshot,price_idr_snapshot,quantity,line_total_idr) VALUES(:order_id,1,'Nasi Goreng Kampung',28000,1,28000)")->execute(['order_id' => $this->orderId]);
        $this->pdo->prepare("INSERT INTO payments(order_id,method,status,amount_idr,paid_at) VALUES(:order_id,'cash_sim','paid_simulated',28000,NOW())")->execute(['order_id' => $this->orderId]);
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
        $this->pdo->prepare("UPDATE dining_tables SET status='available' WHERE id=:id")->execute(['id' => $this->tableId]);
        $this->pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $this->userId]);
    }

    public function testCloseIsRejectedBeforeServeThenSucceedsWithoutChangingOrderToCompleted(): void
    {
        $service = new OperationalService($this->pdo);
        try {
            $service->closeVisit($this->visitId);
            self::fail('Kunjungan tidak boleh ditutup sebelum order disajikan.');
        } catch (CheckoutException $exception) {
            self::assertStringContainsString('belum disajikan', $exception->getMessage());
        }

        self::assertTrue($service->serve($this->orderId));
        self::assertFalse($service->serve($this->orderId));
        self::assertSame('active', $this->pdo->query('SELECT status FROM visits WHERE id=' . $this->visitId)->fetchColumn());
        self::assertSame('occupied', $this->pdo->query('SELECT status FROM dining_tables WHERE id=' . $this->tableId)->fetchColumn());

        self::assertTrue($service->closeVisit($this->visitId));
        self::assertFalse($service->closeVisit($this->visitId));
        self::assertSame('closed', $this->pdo->query('SELECT status FROM visits WHERE id=' . $this->visitId)->fetchColumn());
        self::assertSame('available', $this->pdo->query('SELECT status FROM dining_tables WHERE id=' . $this->tableId)->fetchColumn());
        self::assertSame('served', $this->pdo->query('SELECT status FROM orders WHERE id=' . $this->orderId)->fetchColumn());
    }
}
