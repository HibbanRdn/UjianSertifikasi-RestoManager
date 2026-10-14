<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\SessionStore;
use App\Exceptions\CheckoutException;
use App\Repositories\DiningTableRepository;
use App\Repositories\VisitRepository;
use PDO;
use Throwable;

final class CheckoutService
{
    private const DRAFT_KEY = 'customer.checkout_draft';

    public function __construct(
        private readonly PDO $pdo,
        private readonly SessionStore $session,
        private readonly CartService $cart,
        private readonly DiningTableRepository $tables,
        private readonly VisitRepository $visits,
        private readonly int $estimateMin,
        private readonly int $estimateMax
    ) {
    }

    /** @return array<string, mixed> */
    public function prepareReview(array $user): array
    {
        $userId = (int) $user['id'];
        $cart = $this->cart->details($userId);
        if ($cart['items'] === []) {
            throw new CheckoutException('Keranjang masih kosong.', 'empty_cart');
        }

        $visit = $this->visits->findActiveByUserId($userId);
        $table = $visit;
        if ($visit === null) {
            $tableId = $this->cart->draftTableId($userId);
            $table = $tableId !== null ? $this->tables->findActiveById($tableId) : null;
            if ($table === null || $table['status'] !== 'available') {
                $this->cart->clearDraftTable();
                throw new CheckoutException('Silakan pilih meja yang tersedia.', 'table_required');
            }
        }

        $tableId = $visit !== null ? (int) $visit['table_id'] : (int) $table['id'];
        $fingerprint = $this->fingerprint($cart['items'], (string) $cart['revision'], $tableId);
        $draft = [
            'user_id' => $userId,
            'fingerprint' => $fingerprint,
            'cart_revision' => (string) $cart['revision'],
            'table_id' => $tableId,
            'idempotency_key' => bin2hex(random_bytes(32)),
        ];
        $this->session->put(self::DRAFT_KEY, $draft);

        return [
            'cart' => $cart,
            'table' => $table,
            'visit' => $visit,
            'draft' => $draft,
            'estimate_min' => $this->estimateMin,
            'estimate_max' => $this->estimateMax,
            'customer' => $user,
        ];
    }

    /** @return array<string, mixed> */
    public function paymentDraft(int $userId): array
    {
        $draft = $this->ownedDraft($userId);
        $cart = $this->cart->details($userId);
        if ($cart['items'] === []) {
            throw new CheckoutException('Keranjang masih kosong.', 'empty_cart');
        }

        $visit = $this->visits->findActiveByUserId($userId);
        $tableId = $visit !== null ? (int) $visit['table_id'] : (int) $draft['table_id'];
        $currentFingerprint = $this->fingerprint($cart['items'], (string) $cart['revision'], $tableId);
        if (!hash_equals((string) $draft['fingerprint'], $currentFingerprint)) {
            $this->session->forget(self::DRAFT_KEY);
            throw new CheckoutException('Harga atau ketersediaan menu berubah. Tinjau kembali detail transaksi.', 'changed_review');
        }

        return ['cart' => $cart, 'draft' => $draft, 'table' => $visit ?? $this->tables->findActiveById($tableId)];
    }

    public function finalize(array $user, string $method, string $idempotencyKey, string $fingerprint): int
    {
        if (!in_array($method, ['qris_sim', 'cash_sim'], true)) {
            throw new CheckoutException('Metode pembayaran simulasi tidak valid.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $idempotencyKey)) {
            throw new CheckoutException('Kunci transaksi tidak valid. Muat ulang halaman pembayaran.');
        }

        $userId = (int) $user['id'];
        $existing = $this->findOwnedByIdempotency($idempotencyKey, $userId);
        if ($existing !== null) {
            return $existing;
        }

        $draft = $this->ownedDraft($userId);
        if (!hash_equals((string) $draft['idempotency_key'], $idempotencyKey) || !hash_equals((string) $draft['fingerprint'], $fingerprint)) {
            throw new CheckoutException('Detail checkout sudah tidak berlaku. Tinjau ulang pesanan.', 'changed_review');
        }

        $cartState = $this->cart->details($userId);
        if ($cartState['items'] === []) {
            throw new CheckoutException('Keranjang masih kosong.', 'empty_cart');
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockCustomer($userId);

            $existing = $this->findOwnedByIdempotency($idempotencyKey, $userId);
            if ($existing !== null) {
                $this->pdo->commit();

                return $existing;
            }

            $visitHint = $this->visits->findActiveByUserId($userId);
            $tableId = $visitHint !== null ? (int) $visitHint['table_id'] : (int) $draft['table_id'];
            $table = $this->lockTable($tableId);
            $visit = $this->lockActiveVisit($userId);

            if ($visit === null) {
                if ((int) $table['is_active'] !== 1 || $table['status'] !== 'available') {
                    throw new CheckoutException('Meja ini baru saja digunakan pelanggan lain. Pilih meja lain.', 'table_taken');
                }
                $visitId = $this->createVisit($userId, $tableId, (string) $user['name']);
            } else {
                if ((int) $visit['table_id'] !== $tableId || $table['status'] !== 'occupied') {
                    throw new CheckoutException('Kunjungan aktif tidak konsisten. Hubungi Petugas.');
                }
                $visitId = (int) $visit['id'];
            }

            $lockedItems = $this->lockAndPriceItems($cartState['items']);
            $currentFingerprint = $this->fingerprint($lockedItems, (string) $cartState['revision'], $tableId);
            if (!hash_equals($fingerprint, $currentFingerprint)) {
                throw new CheckoutException('Harga atau ketersediaan menu berubah. Tinjau kembali detail transaksi.', 'changed_review');
            }

            $total = array_sum(array_column($lockedItems, 'line_total_idr'));
            $orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $order = $this->pdo->prepare(
                "INSERT INTO orders (order_code, visit_id, idempotency_key, status, total_idr, estimated_minutes_min, estimated_minutes_max)
                 VALUES (:code, :visit_id, :idempotency_key, 'received', :total, :estimate_min, :estimate_max)"
            );
            $order->execute(['code' => $orderCode, 'visit_id' => $visitId, 'idempotency_key' => $idempotencyKey, 'total' => $total, 'estimate_min' => $this->estimateMin, 'estimate_max' => $this->estimateMax]);
            $orderId = (int) $this->pdo->lastInsertId();

            $itemStatement = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, menu_item_id, menu_name_snapshot, price_idr_snapshot, quantity, line_total_idr)
                 VALUES (:order_id, :menu_item_id, :name, :price, :quantity, :line_total)'
            );
            foreach ($lockedItems as $item) {
                $itemStatement->execute(['order_id' => $orderId, 'menu_item_id' => $item['id'], 'name' => $item['name'], 'price' => $item['price_idr'], 'quantity' => $item['quantity'], 'line_total' => $item['line_total_idr']]);
            }

            $payment = $this->pdo->prepare(
                "INSERT INTO payments (order_id, method, status, amount_idr, paid_at) VALUES (:order_id, :method, 'paid_simulated', :amount, NOW())"
            );
            $payment->execute(['order_id' => $orderId, 'method' => $method, 'amount' => $total]);
            if ($visit === null) {
                $occupy = $this->pdo->prepare("UPDATE dining_tables SET status = 'occupied' WHERE id = :id AND status = 'available'");
                $occupy->execute(['id' => $tableId]);
                if ($occupy->rowCount() !== 1) {
                    throw new CheckoutException('Meja ini baru saja digunakan pelanggan lain. Pilih meja lain.', 'table_taken');
                }
            }

            $this->pdo->commit();
            $this->cart->clear($userId);
            $this->cart->clearDraftTable();
            $this->session->forget(self::DRAFT_KEY);

            return $orderId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof CheckoutException) {
                if ($exception->reason() === 'table_taken') {
                    $this->cart->clearDraftTable();
                    $this->session->forget(self::DRAFT_KEY);
                }
                throw $exception;
            }

            throw new CheckoutException('Pesanan belum dapat dibuat. Silakan coba kembali.');
        }
    }

    /** @return array<string,mixed> */
    private function ownedDraft(int $userId): array
    {
        $draft = $this->session->get(self::DRAFT_KEY);
        if (!is_array($draft) || (int) ($draft['user_id'] ?? 0) !== $userId) {
            throw new CheckoutException('Detail checkout sudah tidak berlaku. Tinjau ulang pesanan.', 'changed_review');
        }

        return $draft;
    }

    /** @param list<array<string,mixed>> $items */
    private function fingerprint(array $items, string $revision, int $tableId): string
    {
        $parts = [$revision, (string) $tableId];
        foreach ($items as $item) {
            $parts[] = implode(':', [(int) $item['id'], (int) $item['quantity'], (int) ($item['price_idr'] ?? 0), (int) ($item['is_available'] ?? 0)]);
        }

        return hash('sha256', implode('|', $parts));
    }

    private function lockCustomer(int $userId): void
    {
        $statement = $this->pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'customer' AND is_active = 1 FOR UPDATE");
        $statement->execute(['id' => $userId]);
        if ($statement->fetchColumn() === false) {
            throw new CheckoutException('Akun pelanggan tidak aktif.');
        }
    }

    /** @return array<string,mixed> */
    private function lockTable(int $tableId): array
    {
        $statement = $this->pdo->prepare('SELECT id, table_code, status, is_active FROM dining_tables WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $tableId]);
        $table = $statement->fetch();
        if (!is_array($table)) {
            throw new CheckoutException('Meja tidak ditemukan.', 'table_taken');
        }

        return $table;
    }

    /** @return array<string,mixed>|null */
    private function lockActiveVisit(int $userId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id, table_id, status FROM visits WHERE user_id = :user_id AND status = 'active' FOR UPDATE");
        $statement->execute(['user_id' => $userId]);
        $visit = $statement->fetch();

        return is_array($visit) ? $visit : null;
    }

    private function createVisit(int $userId, int $tableId, string $customerName): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO visits (visit_code, user_id, table_id, customer_name_snapshot, status)
             VALUES (:code, :user_id, :table_id, :customer_name, 'active')"
        );
        $statement->execute(['code' => 'VIS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))), 'user_id' => $userId, 'table_id' => $tableId, 'customer_name' => $customerName]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param list<array<string,mixed>> $cartItems @return list<array<string,mixed>> */
    private function lockAndPriceItems(array $cartItems): array
    {
        $cartQuantities = [];
        foreach ($cartItems as $item) {
            $cartQuantities[(int) $item['id']] = (int) $item['quantity'];
        }
        $ids = array_keys($cartQuantities);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->pdo->prepare(
            "SELECT m.id, m.name, m.price_idr, m.is_available, m.is_visible, c.is_active AS category_active
             FROM menu_items m INNER JOIN categories c ON c.id = m.category_id
             WHERE m.id IN ($placeholders) ORDER BY m.name FOR UPDATE"
        );
        $statement->execute($ids);
        $rows = $statement->fetchAll();
        if (count($rows) !== count($ids)) {
            throw new CheckoutException('Pilihan menu berubah atau tidak tersedia. Periksa kembali keranjang.', 'changed_review');
        }

        $rowsById = [];
        foreach ($rows as $row) {
            if ((int) $row['is_available'] !== 1 || (int) $row['is_visible'] !== 1 || (int) $row['category_active'] !== 1) {
                throw new CheckoutException('Pilihan menu berubah atau tidak tersedia. Periksa kembali keranjang.', 'changed_review');
            }
            $rowsById[(int) $row['id']] = $row;
        }

        $locked = [];
        foreach ($cartItems as $cartItem) {
            $row = $rowsById[(int) $cartItem['id']];
            $quantity = $cartQuantities[(int) $row['id']];
            $locked[] = [
                'id' => (int) $row['id'], 'name' => $row['name'], 'price_idr' => (int) $row['price_idr'],
                'is_available' => (int) $row['is_available'], 'quantity' => $quantity,
                'line_total_idr' => (int) $row['price_idr'] * $quantity,
            ];
        }

        return $locked;
    }

    private function findOwnedByIdempotency(string $key, int $userId): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT o.id FROM orders o INNER JOIN visits v ON v.id = o.visit_id WHERE o.idempotency_key = :key AND v.user_id = :user_id LIMIT 1'
        );
        $statement->execute(['key' => $key, 'user_id' => $userId]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }
}
