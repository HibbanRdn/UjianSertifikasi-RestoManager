<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CheckoutException;
use PDO;
use Throwable;

final class OperationalService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function serve(int $orderId): bool
    {
        $hint = $this->orderHint($orderId);
        if ($hint === null) {
            throw new CheckoutException('Pesanan tidak ditemukan.');
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockUser((int) $hint['user_id']);
            $this->lockTable((int) $hint['table_id']);
            $this->lockVisit((int) $hint['visit_id']);
            $statement = $this->pdo->prepare(
                'SELECT o.status,p.status AS payment_status FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.id=:id FOR UPDATE'
            );
            $statement->execute(['id' => $orderId]);
            $order = $statement->fetch();
            if (!is_array($order)) {
                throw new CheckoutException('Pesanan tidak ditemukan.');
            }
            if ($order['status'] === 'served') {
                $this->pdo->commit();

                return false;
            }
            if ($order['status'] !== 'received' || $order['payment_status'] !== 'paid_simulated') {
                throw new CheckoutException('Pesanan belum memenuhi syarat untuk disajikan.');
            }

            $update = $this->pdo->prepare("UPDATE orders SET status='served',served_at=NOW() WHERE id=:id AND status='received'");
            $update->execute(['id' => $orderId]);
            $this->pdo->commit();

            return $update->rowCount() === 1;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception instanceof CheckoutException ? $exception : new CheckoutException('Pesanan belum dapat ditandai disajikan.');
        }
    }

    public function closeVisit(int $visitId): bool
    {
        $hint = $this->visitHint($visitId);
        if ($hint === null) {
            throw new CheckoutException('Kunjungan tidak ditemukan.');
        }

        try {
            $this->pdo->beginTransaction();
            $this->lockUser((int) $hint['user_id']);
            $table = $this->lockTable((int) $hint['table_id']);
            $visit = $this->lockVisit($visitId);
            if ($visit['status'] === 'closed') {
                $this->pdo->commit();

                return false;
            }
            if ($visit['status'] !== 'active' || $table['status'] !== 'occupied') {
                throw new CheckoutException('Kunjungan atau status meja tidak konsisten.');
            }

            $orders = $this->pdo->prepare(
                'SELECT o.id,o.status,p.status AS payment_status FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE o.visit_id=:visit_id FOR UPDATE'
            );
            $orders->execute(['visit_id' => $visitId]);
            $rows = $orders->fetchAll();
            if ($rows === []) {
                throw new CheckoutException('Kunjungan tanpa pesanan tidak dapat ditutup dari alur ini.');
            }
            foreach ($rows as $order) {
                if ($order['status'] !== 'served') {
                    throw new CheckoutException('Masih ada pesanan yang belum disajikan.');
                }
                if ($order['payment_status'] !== 'paid_simulated') {
                    throw new CheckoutException('Pembayaran simulasi belum selesai.');
                }
            }

            $close = $this->pdo->prepare("UPDATE visits SET status='closed',closed_at=NOW() WHERE id=:id AND status='active'");
            $close->execute(['id' => $visitId]);
            $release = $this->pdo->prepare("UPDATE dining_tables SET status='available' WHERE id=:id AND status='occupied'");
            $release->execute(['id' => $hint['table_id']]);
            if ($close->rowCount() !== 1 || $release->rowCount() !== 1) {
                throw new CheckoutException('Kunjungan berubah saat diproses. Silakan muat ulang.');
            }
            $this->pdo->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception instanceof CheckoutException ? $exception : new CheckoutException('Kunjungan belum dapat ditutup.');
        }
    }

    /** @return array<string,mixed>|null */
    private function orderHint(int $orderId): ?array
    {
        $statement = $this->pdo->prepare('SELECT o.id,o.visit_id,v.user_id,v.table_id FROM orders o JOIN visits v ON v.id=o.visit_id WHERE o.id=:id');
        $statement->execute(['id' => $orderId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string,mixed>|null */
    private function visitHint(int $visitId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id,user_id,table_id,status FROM visits WHERE id=:id');
        $statement->execute(['id' => $visitId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    private function lockUser(int $userId): void
    {
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE id=:id FOR UPDATE');
        $statement->execute(['id' => $userId]);
        if ($statement->fetchColumn() === false) {
            throw new CheckoutException('Akun pelanggan tidak ditemukan.');
        }
    }

    /** @return array<string,mixed> */
    private function lockTable(int $tableId): array
    {
        $statement = $this->pdo->prepare('SELECT id,status FROM dining_tables WHERE id=:id FOR UPDATE');
        $statement->execute(['id' => $tableId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            throw new CheckoutException('Meja tidak ditemukan.');
        }

        return $row;
    }

    /** @return array<string,mixed> */
    private function lockVisit(int $visitId): array
    {
        $statement = $this->pdo->prepare('SELECT id,status,table_id FROM visits WHERE id=:id FOR UPDATE');
        $statement->execute(['id' => $visitId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            throw new CheckoutException('Kunjungan tidak ditemukan.');
        }

        return $row;
    }
}
