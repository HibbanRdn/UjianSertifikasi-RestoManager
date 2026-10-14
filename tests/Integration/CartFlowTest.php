<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\DiningTableRepository;
use App\Repositories\MenuItemRepository;
use App\Services\CartService;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class CartFlowTest extends TestCase
{
    public function testPendingItemIsConsumedExactlyOnceAndCartIsBoundToUser(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $session = new ArraySession();
        $cart = new CartService($session, new MenuItemRepository($app['pdo']));

        $cart->rememberPending(1);
        self::assertTrue($cart->consumePending(101));
        self::assertFalse($cart->consumePending(101));
        self::assertCount(1, $cart->details(101)['items']);
        self::assertSame(1, $cart->details(101)['items'][0]['quantity']);
        self::assertSame([], $cart->details(202)['items']);
    }

    public function testQuantityIsBoundedAndTotalAlwaysComesFromDatabasePrice(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $cart = new CartService(new ArraySession(), new MenuItemRepository($app['pdo']));

        $cart->add(101, 1, 200);
        $details = $cart->details(101);

        self::assertSame(99, $details['items'][0]['quantity']);
        self::assertSame(99 * 28000, $details['total_idr']);
    }

    public function testDraftTableRemainsAvailableInDatabase(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $session = new ArraySession();
        $cart = new CartService($session, new MenuItemRepository($app['pdo']));
        $tables = new DiningTableRepository($app['pdo']);
        $table = $tables->available()[0];

        $cart->setDraftTable(101, (int) $table['id']);

        self::assertSame((int) $table['id'], $cart->draftTableId(101));
        self::assertNull($cart->draftTableId(202));
        self::assertTrue($tables->isAvailable((int) $table['id']));
    }
}
