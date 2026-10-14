<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use App\Services\LoginThrottle;
use App\Services\RolePolicy;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class SecurityFoundationTest extends TestCase
{
    public function testCsrfTokenIsStableAndRejectsMissingOrDifferentValue(): void
    {
        $session = new ArraySession();
        $csrf = new Csrf($session);
        $token = $csrf->token();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $csrf->token());
        self::assertTrue($csrf->verify($token));
        self::assertFalse($csrf->verify(''));
        self::assertFalse($csrf->verify(str_repeat('a', 64)));
    }

    public function testLoginThrottleBlocksAtLimitAndCanBeCleared(): void
    {
        $session = new ArraySession();
        $throttle = new LoginThrottle($session, 2, 300);
        $throttle->recordFailure();
        self::assertSame(0, $throttle->retryAfterSeconds());
        $throttle->recordFailure();
        self::assertGreaterThan(0, $throttle->retryAfterSeconds());
        $throttle->clear();
        self::assertSame(0, $throttle->retryAfterSeconds());
    }

    public function testPermissionMatrix(): void
    {
        $policy = new RolePolicy();

        self::assertTrue($policy->canAccessCustomer('customer'));
        self::assertFalse($policy->canAccessCustomer('staff'));
        self::assertFalse($policy->canAccessCustomer('admin'));
        self::assertFalse($policy->canAccessStaff('customer'));
        self::assertTrue($policy->canAccessStaff('staff'));
        self::assertTrue($policy->canAccessStaff('admin'));
        self::assertFalse($policy->canAccessAdmin('customer'));
        self::assertFalse($policy->canAccessAdmin('staff'));
        self::assertTrue($policy->canAccessAdmin('admin'));
    }

    public function testHtmlEscapingNeutralizesExecutableMarkup(): void
    {
        $unsafe = '<script>alert("xss")</script>';

        self::assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            e($unsafe)
        );
    }
}
