<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

final class DatabaseFoundationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $this->pdo = $app['database']->connect();
    }

    public function testEightCoreTablesExistAndUseInnoDb(): void
    {
        $rows = $this->pdo->query(
            "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME"
        )->fetchAll();

        $names = array_column($rows, 'TABLE_NAME');
        sort($names, SORT_STRING);
        self::assertSame(
            ['categories', 'dining_tables', 'menu_items', 'order_items', 'orders', 'payments', 'users', 'visits'],
            $names
        );

        foreach ($rows as $row) {
            self::assertSame('InnoDB', $row['ENGINE']);
        }
    }

    public function testSeedDataExists(): void
    {
        self::assertSame(2, (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'staff')")->fetchColumn());
        self::assertSame(3, (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn());
        self::assertSame(6, (int) $this->pdo->query('SELECT COUNT(*) FROM dining_tables')->fetchColumn());
        self::assertSame(10, (int) $this->pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn());
    }

    public function testAllTenMenuImagesAndSimulationQrisAreLocalAssets(): void
    {
        $paths = $this->pdo->query('SELECT image_path FROM menu_items ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        self::assertCount(10, $paths);

        $publicRoot = dirname(__DIR__, 2) . '/public';
        foreach ($paths as $path) {
            self::assertStringStartsWith('/assets/img/menu/', $path);
            self::assertFileExists($publicRoot . $path);
        }

        self::assertFileExists($publicRoot . '/assets/img/qris-simulasi.svg');
    }

    public function testSeedPasswordsUseValidPhpHashes(): void
    {
        $statement = $this->pdo->query("SELECT email, password_hash FROM users WHERE role IN ('admin', 'staff')");
        $hashes = array_column($statement->fetchAll(), 'password_hash', 'email');

        self::assertTrue(password_verify('Admin123!', $hashes['admin@restomanager.local']));
        self::assertTrue(password_verify('Staff123!', $hashes['staff@restomanager.local']));
    }

    public function testActiveVisitUniqueIndexesExist(): void
    {
        $statement = $this->pdo->query(
            "SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'visits'
               AND NON_UNIQUE = 0
               AND INDEX_NAME IN ('uq_visits_active_user', 'uq_visits_active_table')"
        );

        self::assertSame(2, (int) $statement->fetchColumn());
    }

    public function testTransactionRollbackWorks(): void
    {
        $name = 'TEMP-ROLLBACK-' . bin2hex(random_bytes(4));

        $this->pdo->beginTransaction();
        $statement = $this->pdo->prepare('INSERT INTO categories (name, is_active) VALUES (:name, 1)');
        $statement->execute(['name' => $name]);
        $this->pdo->rollBack();

        $check = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name');
        $check->execute(['name' => $name]);
        self::assertSame(0, (int) $check->fetchColumn());
    }
}
