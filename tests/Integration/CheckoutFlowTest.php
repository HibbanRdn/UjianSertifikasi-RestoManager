<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exceptions\CheckoutException;
use App\Repositories\DiningTableRepository;
use App\Repositories\MenuItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\VisitRepository;
use App\Services\CartService;
use App\Services\CheckoutService;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class CheckoutFlowTest extends TestCase
{
    private PDO $pdo;
    private int $userId;
    private int $tableId;

    protected function setUp(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $this->pdo = $app['pdo'];
        $email = 'checkout-' . bin2hex(random_bytes(6)) . '@example.test';
        $statement = $this->pdo->prepare("INSERT INTO users (name,email,phone,password_hash,role,is_active) VALUES ('Checkout Test',:email,'081234567890',:hash,'customer',1)");
        $statement->execute(['email' => $email, 'hash' => password_hash('Checkout123!', PASSWORD_DEFAULT)]);
        $this->userId = (int) $this->pdo->lastInsertId();
        $this->tableId = (int) $this->pdo->query("SELECT id FROM dining_tables WHERE status='available' AND is_active=1 ORDER BY id DESC LIMIT 1")->fetchColumn();
    }

    protected function tearDown(): void
    {
        if (!isset($this->userId)) {
            return;
        }
        $visits = $this->pdo->prepare('SELECT id FROM visits WHERE user_id = :user_id');
        $visits->execute(['user_id' => $this->userId]);
        $visitIds = array_map('intval', array_column($visits->fetchAll(), 'id'));
        if ($visitIds !== []) {
            $placeholders = implode(',', array_fill(0, count($visitIds), '?'));
            $orders = $this->pdo->prepare("SELECT id FROM orders WHERE visit_id IN ($placeholders)");
            $orders->execute($visitIds);
            $orderIds = array_map('intval', array_column($orders->fetchAll(), 'id'));
            if ($orderIds !== []) {
                $orderPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
                $this->pdo->prepare("DELETE FROM payments WHERE order_id IN ($orderPlaceholders)")->execute($orderIds);
                $this->pdo->prepare("DELETE FROM order_items WHERE order_id IN ($orderPlaceholders)")->execute($orderIds);
                $this->pdo->prepare("DELETE FROM orders WHERE id IN ($orderPlaceholders)")->execute($orderIds);
            }
            $this->pdo->prepare("DELETE FROM visits WHERE id IN ($placeholders)")->execute($visitIds);
        }
        $this->pdo->prepare("UPDATE dining_tables SET status='available' WHERE id=:id")->execute(['id' => $this->tableId]);
        $this->pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $this->userId]);
    }

    public static function paymentMethods(): array
    {
        return [['qris_sim'], ['cash_sim']];
    }

    #[DataProvider('paymentMethods')]
    public function testFirstCheckoutIsAtomicAndIdempotent(string $method): void
    {
        [$service, $cart] = $this->services();
        $cart->setDraftTable($this->userId, $this->tableId);
        $cart->add($this->userId, 2, 2);
        $user = ['id' => $this->userId, 'name' => 'Checkout Test'];
        $review = $service->prepareReview($user);

        $orderId = $service->finalize($user, $method, $review['draft']['idempotency_key'], $review['draft']['fingerprint']);
        $sameOrderId = $service->finalize($user, $method, $review['draft']['idempotency_key'], $review['draft']['fingerprint']);

        self::assertSame($orderId, $sameOrderId);
        $statement = $this->pdo->prepare('SELECT o.total_idr,p.amount_idr,p.method,p.status,v.status AS visit_status,t.status AS table_status FROM orders o JOIN payments p ON p.order_id=o.id JOIN visits v ON v.id=o.visit_id JOIN dining_tables t ON t.id=v.table_id WHERE o.id=:id');
        $statement->execute(['id' => $orderId]);
        $row = $statement->fetch();
        self::assertSame(52000, (int) $row['total_idr']);
        self::assertSame(52000, (int) $row['amount_idr']);
        self::assertSame($method, $row['method']);
        self::assertSame('paid_simulated', $row['status']);
        self::assertSame('active', $row['visit_status']);
        self::assertSame('occupied', $row['table_status']);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM order_items WHERE order_id=' . $orderId)->fetchColumn());
    }

    public function testPriceChangeAfterReviewIsRejectedWithoutPartialRows(): void
    {
        [$service, $cart] = $this->services();
        $cart->setDraftTable($this->userId, $this->tableId);
        $cart->add($this->userId, 2);
        $user = ['id' => $this->userId, 'name' => 'Checkout Test'];
        $review = $service->prepareReview($user);
        $originalPrice = (int) $this->pdo->query('SELECT price_idr FROM menu_items WHERE id=2')->fetchColumn();

        try {
            $this->pdo->exec('UPDATE menu_items SET price_idr = price_idr + 1000 WHERE id=2');
            $service->finalize($user, 'qris_sim', $review['draft']['idempotency_key'], $review['draft']['fingerprint']);
            self::fail('Checkout harus ditolak ketika harga berubah.');
        } catch (CheckoutException $exception) {
            self::assertSame('changed_review', $exception->reason());
            self::assertStringContainsString('Tinjau kembali', $exception->getMessage());
        } finally {
            $restore = $this->pdo->prepare('UPDATE menu_items SET price_idr=:price WHERE id=2');
            $restore->execute(['price' => $originalPrice]);
        }

        $visits = $this->pdo->prepare('SELECT COUNT(*) FROM visits WHERE user_id=:user_id');
        $visits->execute(['user_id' => $this->userId]);
        self::assertSame(0, (int) $visits->fetchColumn());
        self::assertSame('available', $this->pdo->query('SELECT status FROM dining_tables WHERE id=' . $this->tableId)->fetchColumn());
        self::assertCount(1, $cart->details($this->userId)['items']);
    }

    public function testAdditionalOrderReusesActiveVisitAndDoesNotReallocateTable(): void
    {
        [$service, $cart] = $this->services();
        $user = ['id' => $this->userId, 'name' => 'Checkout Test'];
        $cart->setDraftTable($this->userId, $this->tableId);
        $cart->add($this->userId, 3);
        $first = $service->prepareReview($user);
        $firstOrderId = $service->finalize($user, 'qris_sim', $first['draft']['idempotency_key'], $first['draft']['fingerprint']);

        $cart->add($this->userId, 5, 2);
        $second = $service->prepareReview($user);
        $secondOrderId = $service->finalize($user, 'cash_sim', $second['draft']['idempotency_key'], $second['draft']['fingerprint']);

        self::assertNotSame($firstOrderId, $secondOrderId);
        $statement = $this->pdo->prepare('SELECT COUNT(DISTINCT visit_id) AS visits, COUNT(*) AS orders FROM orders WHERE id IN (:first_id,:second_id)');
        $statement->execute(['first_id' => $firstOrderId, 'second_id' => $secondOrderId]);
        $counts = $statement->fetch();
        self::assertSame(1, (int) $counts['visits']);
        self::assertSame(2, (int) $counts['orders']);
        self::assertSame('occupied', $this->pdo->query('SELECT status FROM dining_tables WHERE id=' . $this->tableId)->fetchColumn());
    }

    public function testStaleConcurrentTableSelectionIsRejectedAtCommit(): void
    {
        [$firstCheckout, $firstCart] = $this->services();
        $email = 'competitor-' . bin2hex(random_bytes(5)) . '@example.test';
        $insert = $this->pdo->prepare("INSERT INTO users(name,email,phone,password_hash,role,is_active) VALUES('Competitor',:email,'08123',:hash,'customer',1)");
        $insert->execute(['email' => $email, 'hash' => password_hash('Competitor123!', PASSWORD_DEFAULT)]);
        $secondUserId = (int) $this->pdo->lastInsertId();

        try {
            $secondSession = new ArraySession();
            $menu = new MenuItemRepository($this->pdo);
            $tables = new DiningTableRepository($this->pdo);
            $visits = new VisitRepository($this->pdo);
            $secondCart = new CartService($secondSession, $menu);
            $secondCheckout = new CheckoutService($this->pdo, $secondSession, $secondCart, $tables, $visits, 15, 25);

            $firstCart->setDraftTable($this->userId, $this->tableId);
            $firstCart->add($this->userId, 1);
            $secondCart->setDraftTable($secondUserId, $this->tableId);
            $secondCart->add($secondUserId, 1);
            $firstUser = ['id' => $this->userId, 'name' => 'Checkout Test'];
            $secondUser = ['id' => $secondUserId, 'name' => 'Competitor'];
            $firstReview = $firstCheckout->prepareReview($firstUser);
            $secondReview = $secondCheckout->prepareReview($secondUser);

            $firstCheckout->finalize($firstUser, 'qris_sim', $firstReview['draft']['idempotency_key'], $firstReview['draft']['fingerprint']);

            $this->expectException(CheckoutException::class);
            $secondCheckout->finalize($secondUser, 'cash_sim', $secondReview['draft']['idempotency_key'], $secondReview['draft']['fingerprint']);
        } finally {
            $this->pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $secondUserId]);
        }
    }

    public function testReceiptOwnershipAndHiddenMenuAreEnforcedServerSide(): void
    {
        [$service, $cart] = $this->services();
        $cart->setDraftTable($this->userId, $this->tableId);
        $cart->add($this->userId, 1);
        $user = ['id' => $this->userId, 'name' => 'Checkout Test'];
        $review = $service->prepareReview($user);
        $orderId = $service->finalize($user, 'qris_sim', $review['draft']['idempotency_key'], $review['draft']['fingerprint']);
        $orders = new OrderRepository($this->pdo);

        self::assertNotNull($orders->receiptOwnedBy($orderId, $this->userId));
        self::assertNull($orders->receiptOwnedBy($orderId, $this->userId + 999999));

        $visible = (int) $this->pdo->query('SELECT is_visible FROM menu_items WHERE id=2')->fetchColumn();
        try {
            $this->pdo->exec('UPDATE menu_items SET is_visible=0 WHERE id=2');
            $cart->add($this->userId, 2);
            $this->expectException(CheckoutException::class);
            $service->prepareReview($user);
        } finally {
            $this->pdo->prepare('UPDATE menu_items SET is_visible=:visible WHERE id=2')->execute(['visible' => $visible]);
        }
    }

    /** @return array{CheckoutService,CartService} */
    private function services(): array
    {
        $session = new ArraySession();
        $menu = new MenuItemRepository($this->pdo);
        $tables = new DiningTableRepository($this->pdo);
        $visits = new VisitRepository($this->pdo);
        $cart = new CartService($session, $menu);
        $checkout = new CheckoutService($this->pdo, $session, $cart, $tables, $visits, 15, 25);

        return [$checkout, $cart];
    }
}
