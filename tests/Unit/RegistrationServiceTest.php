<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Services\RegistrationService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryUserRepository;

final class RegistrationServiceTest extends TestCase
{
    public function testRegistrationNormalizesInputHashesPasswordAndForcesCustomerRole(): void
    {
        $repository = new InMemoryUserRepository();
        $service = new RegistrationService($repository);

        $user = $service->register([
            'name' => '  Rani Pelanggan  ',
            'email' => '  RANI@EXAMPLE.COM ',
            'phone' => '0812-3456-7890',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'role' => 'admin',
        ]);

        self::assertSame('Rani Pelanggan', $user['name']);
        self::assertSame('rani@example.com', $user['email']);
        self::assertSame('081234567890', $user['phone']);
        self::assertSame('customer', $user['role']);
        self::assertTrue(password_verify('Rahasia123!', (string) $user['password_hash']));
        self::assertNotSame('Rahasia123!', $user['password_hash']);
    }

    public function testRegistrationRejectsInvalidFields(): void
    {
        $service = new RegistrationService(new InMemoryUserRepository());

        try {
            $service->register([
                'name' => 'A',
                'email' => 'bukan-email',
                'phone' => '123',
                'password' => 'pendek',
                'password_confirmation' => 'beda',
            ]);
            self::fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $exception) {
            self::assertSame(['name', 'email', 'phone', 'password'], array_keys($exception->errors()));
        }
    }

    public function testRegistrationRejectsDuplicateEmail(): void
    {
        $repository = new InMemoryUserRepository([$this->userFixture()]);
        $service = new RegistrationService($repository);

        $this->expectException(ValidationException::class);
        $service->register([
            'name' => 'Nama Baru',
            'email' => 'customer@example.test',
            'phone' => '081234567890',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
        ]);
    }

    /** @return array<string, mixed> */
    private function userFixture(): array
    {
        return [
            'id' => 1,
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'phone' => '081234567890',
            'password_hash' => password_hash('Rahasia123!', PASSWORD_DEFAULT),
            'role' => 'customer',
            'is_active' => 1,
        ];
    }
}
