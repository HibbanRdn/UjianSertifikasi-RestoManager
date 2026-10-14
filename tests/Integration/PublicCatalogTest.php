<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\MenuItemRepository;
use PHPUnit\Framework\TestCase;

final class PublicCatalogTest extends TestCase
{
    public function testSearchUsesPreparedParametersAndReturnsRelevantMenu(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $repository = new MenuItemRepository($app['pdo']);

        $results = $repository->publicCatalog(search: 'ayam');
        $names = array_column($results, 'name');

        self::assertContains('Ayam Bakar Madu', $names);
        self::assertContains('Soto Ayam', $names);
        self::assertNotContains('Es Teh Manis', $names);
    }

    public function testSeededMenuImagesPointToExistingPublicAssets(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $repository = new MenuItemRepository($app['pdo']);

        foreach ($repository->publicCatalog() as $item) {
            $path = dirname(__DIR__, 2) . '/public' . $item['image_path'];
            self::assertFileExists($path, sprintf('Aset untuk %s harus tersedia.', $item['name']));
        }
    }
}
