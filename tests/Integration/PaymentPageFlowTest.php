<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class PaymentPageFlowTest extends TestCase
{
    public function testMethodSelectorContainsNoPaymentDetailOrFinalizeForm(): void
    {
        $selector = file_get_contents(dirname(__DIR__, 2) . '/views/checkout/payment.php');

        self::assertStringContainsString('/checkout/payment/qris', $selector);
        self::assertStringContainsString('/checkout/payment/cash', $selector);
        self::assertStringNotContainsString('/checkout/finalize', $selector);
        self::assertStringNotContainsString('KODE DUMMY', $selector);
    }

    public function testEachDetailPageFinalizesOnlyItsOwnMethod(): void
    {
        $qris = file_get_contents(dirname(__DIR__, 2) . '/views/checkout/payment-qris.php');
        $cash = file_get_contents(dirname(__DIR__, 2) . '/views/checkout/payment-cash.php');

        self::assertSame(1, substr_count($qris, '/checkout/finalize'));
        self::assertStringContainsString('value="qris_sim"', $qris);
        self::assertStringNotContainsString('cash_sim', $qris);
        self::assertSame(1, substr_count($cash, '/checkout/finalize'));
        self::assertStringContainsString('value="cash_sim"', $cash);
        self::assertStringNotContainsString('qris_sim', $cash);
    }

    public function testRoutesExposeBothDetailPages(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2) . '/config/routes.php');

        self::assertStringContainsString("'/checkout/payment/qris'", $routes);
        self::assertStringContainsString("'/checkout/payment/cash'", $routes);
    }
}
