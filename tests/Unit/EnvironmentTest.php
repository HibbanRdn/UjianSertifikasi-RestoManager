<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::load(dirname(__DIR__, 2) . '/.env');
    }

    public function testEnvironmentUsesExpectedTimezone(): void
    {
        self::assertSame('Asia/Jakarta', Environment::get('APP_TIMEZONE'));
    }

    public function testDatabaseNameIsDedicatedToProject(): void
    {
        self::assertSame('restomanager', Environment::get('DB_DATABASE'));
    }
}
