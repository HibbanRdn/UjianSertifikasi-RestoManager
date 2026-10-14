<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exceptions\CheckoutException;
use App\Repositories\AdminRepository;
use App\Services\AdminService;
use App\Services\MenuImageUploadService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AdminManagementTest extends TestCase
{
    private PDO $pdo;
    private AdminService $service;

    protected function setUp(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $this->pdo = $app['pdo'];
        $this->service = $app['services']['admin'];
    }

    public function testAdminCanMaintainSoftDisabledMasterDataAndStaff(): void
    {
        $service = $this->service;
        $suffix = strtoupper(bin2hex(random_bytes(3)));
        $categoryName = 'TEST-' . $suffix;
        $tableCode = 'T-' . $suffix;
        $staffEmail = strtolower($suffix) . '@admin-test.local';

        try {
            $service->saveCategory(null, $categoryName);
            $categoryId = (int) $this->pdo->query("SELECT id FROM categories WHERE name=" . $this->pdo->quote($categoryName))->fetchColumn();
            $service->toggleCategory($categoryId);
            self::assertSame(0, (int) $this->pdo->query('SELECT is_active FROM categories WHERE id=' . $categoryId)->fetchColumn());

            $service->saveMenu(null, ['category_id' => (string) $categoryId, 'name' => 'Menu ' . $suffix, 'description' => 'Data uji', 'price_idr' => '12345']);
            $menuId = (int) $this->pdo->query("SELECT id FROM menu_items WHERE name=" . $this->pdo->quote('Menu ' . $suffix))->fetchColumn();
            $service->toggleMenu($menuId, 'is_visible');
            self::assertSame(0, (int) $this->pdo->query('SELECT is_visible FROM menu_items WHERE id=' . $menuId)->fetchColumn());

            $service->saveTable(null, $tableCode);
            $tableId = (int) $this->pdo->query("SELECT id FROM dining_tables WHERE table_code=" . $this->pdo->quote($tableCode))->fetchColumn();
            $service->toggleTable($tableId);
            self::assertSame(0, (int) $this->pdo->query('SELECT is_active FROM dining_tables WHERE id=' . $tableId)->fetchColumn());

            $service->saveStaff(null, ['name' => 'Petugas Test', 'email' => $staffEmail, 'phone' => '08123', 'password' => 'StaffTest123!']);
            $staffId = (int) $this->pdo->query("SELECT id FROM users WHERE email=" . $this->pdo->quote($staffEmail))->fetchColumn();
            $service->toggleStaff($staffId);
            self::assertSame('staff', $this->pdo->query('SELECT role FROM users WHERE id=' . $staffId)->fetchColumn());
            self::assertSame(0, (int) $this->pdo->query('SELECT is_active FROM users WHERE id=' . $staffId)->fetchColumn());

            $report = (new AdminRepository($this->pdo))->report(date('Y-m-01'), date('Y-m-d'));
            self::assertArrayHasKey('simulated_total', $report['summary']);
            self::assertIsArray($report['orders']);
            self::assertIsArray($report['top_items']);
        } finally {
            if (isset($menuId)) {
                $this->pdo->prepare('DELETE FROM menu_items WHERE id=:id')->execute(['id' => $menuId]);
            }
            if (isset($categoryId)) {
                $this->pdo->prepare('DELETE FROM categories WHERE id=:id')->execute(['id' => $categoryId]);
            }
            if (isset($tableId)) {
                $this->pdo->prepare('DELETE FROM dining_tables WHERE id=:id')->execute(['id' => $tableId]);
            }
            if (isset($staffId)) {
                $this->pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $staffId]);
            }
        }
    }

    public function testHidingMenuDoesNotMutateExistingOrderSnapshot(): void
    {
        $service = $this->service;
        $snapshotBefore = $this->pdo->query('SELECT menu_name_snapshot,price_idr_snapshot FROM order_items ORDER BY id LIMIT 1')->fetch();
        if (!is_array($snapshotBefore)) {
            self::markTestSkipped('Belum ada order manual untuk memeriksa snapshot historis.');
        }
        $visibleBefore = (int) $this->pdo->query('SELECT is_visible FROM menu_items WHERE id=1')->fetchColumn();
        try {
            $service->toggleMenu(1, 'is_visible');
            $snapshotAfter = $this->pdo->query('SELECT menu_name_snapshot,price_idr_snapshot FROM order_items ORDER BY id LIMIT 1')->fetch();
            self::assertSame($snapshotBefore, $snapshotAfter);
        } finally {
            $current = (int) $this->pdo->query('SELECT is_visible FROM menu_items WHERE id=1')->fetchColumn();
            if ($current !== $visibleBefore) {
                $service->toggleMenu(1, 'is_visible');
            }
        }
    }

    public function testEditingMenuWithoutNewUploadPreservesExistingImage(): void
    {
        $row = $this->pdo->query('SELECT id,category_id,name,description,price_idr,image_path FROM menu_items ORDER BY id LIMIT 1')->fetch();
        self::assertIsArray($row);

        $this->service->saveMenu((int) $row['id'], [
            'category_id' => (string) $row['category_id'],
            'name' => (string) $row['name'],
            'description' => (string) $row['description'],
            'price_idr' => (string) $row['price_idr'],
        ]);

        $statement = $this->pdo->prepare('SELECT image_path FROM menu_items WHERE id=:id');
        $statement->execute(['id' => $row['id']]);
        self::assertSame($row['image_path'], $statement->fetchColumn());
    }

    public function testUploadedFileIsRemovedWhenDatabaseRejectsMenu(): void
    {
        $existing = $this->pdo->query('SELECT category_id,name FROM menu_items ORDER BY id LIMIT 1')->fetch();
        self::assertIsArray($existing);
        $directory = sys_get_temp_dir() . '/restomanager-db-failure-' . bin2hex(random_bytes(5));
        mkdir($directory, 0755, true);
        $source = $directory . '/source.png';
        $image = imagecreatetruecolor(4, 4);
        imagepng($image, $source);
        imagedestroy($image);
        $uploader = new MenuImageUploadService($directory, '/uploads/menu', 5 * 1024 * 1024, 6000, static fn (string $path): bool => is_file($path), static fn (string $from, string $to): bool => rename($from, $to));
        $service = new AdminService($this->pdo, $uploader);

        try {
            $service->saveMenu(null, ['category_id' => (string) $existing['category_id'], 'name' => (string) $existing['name'], 'description' => 'Duplikat', 'price_idr' => '10000'], ['error' => UPLOAD_ERR_OK, 'tmp_name' => $source, 'size' => filesize($source)]);
            self::fail('Nama duplikat harus ditolak database.');
        } catch (CheckoutException $exception) {
            self::assertStringContainsString('sudah digunakan', $exception->getMessage());
            self::assertSame([], glob($directory . '/*') ?: [], 'Berkas baru harus dibersihkan setelah kegagalan database.');
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
