<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AuthService;
use App\Services\LoginThrottle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;
use Tests\Support\InMemoryUserRepository;

final class AuthServiceTest extends TestCase
{
    public function testSuccessfulLoginRegeneratesSessionAndClearsPreviousCustomerState(): void
    {
        $session = new ArraySession([
            'customer.cart' => ['menu' => 1],
            'customer.selected_table_id' => 4,
            'customer.checkout_draft' => ['name' => 'Lama'],
            'customer.pending_add' => ['menu_item_id' => 8],
        ]);
        $service = $this->service($session);

        self::assertTrue($service->attempt(' CUSTOMER@EXAMPLE.TEST ', 'Rahasia123!'));
        self::assertSame(1, $session->regenerations);
        self::assertSame(1, $session->get('auth.user_id'));
        self::assertFalse($session->has('customer.cart'));
        self::assertFalse($session->has('customer.selected_table_id'));
        self::assertFalse($session->has('customer.checkout_draft'));
        self::assertSame(['menu_item_id' => 8], $session->get('customer.pending_add'));
    }

    public function testFailedAndInactiveLoginDoNotAuthenticate(): void
    {
        $session = new ArraySession();
        $repository = new InMemoryUserRepository([
            $this->fixture(1, 'customer', true),
            $this->fixture(2, 'staff', false),
        ]);
        $service = new AuthService($repository, $session, new LoginThrottle($session));

        self::assertFalse($service->attempt('customer@example.test', 'salah'));
        self::assertFalse($service->attempt('staff@example.test', 'Rahasia123!'));
        self::assertFalse($service->check());
        self::assertFalse($session->has('auth.user_id'));
    }

    public function testLogoutInvalidatesEntireSession(): void
    {
        $session = new ArraySession(['customer.cart' => ['menu' => 1], '_csrf_token' => 'token']);
        $service = $this->service($session);
        self::assertTrue($service->attempt('customer@example.test', 'Rahasia123!'));

        $service->logout();

        self::assertSame([], $session->data);
        self::assertSame(1, $session->invalidations);
        self::assertFalse($service->check());
    }

    #[DataProvider('redirectProvider')]
    public function testRoleRedirects(string $role, string $expected): void
    {
        $session = new ArraySession();
        $repository = new InMemoryUserRepository([$this->fixture(1, $role, true)]);
        $service = new AuthService($repository, $session, new LoginThrottle($session));
        self::assertTrue($service->attempt($role . '@example.test', 'Rahasia123!'));

        self::assertSame($expected, $service->redirectPath('/history'));
    }

    /** @return iterable<string, array{string,string}> */
    public static function redirectProvider(): iterable
    {
        yield 'customer dapat kembali ke riwayat' => ['customer', '/history'];
        yield 'staff ke operasional' => ['staff', '/staff/dashboard'];
        yield 'admin ke administrasi' => ['admin', '/admin/dashboard'];
    }

    private function service(ArraySession $session): AuthService
    {
        $repository = new InMemoryUserRepository([$this->fixture(1, 'customer', true)]);

        return new AuthService($repository, $session, new LoginThrottle($session));
    }

    /** @return array<string, mixed> */
    private function fixture(int $id, string $role, bool $active): array
    {
        return [
            'id' => $id,
            'name' => ucfirst($role),
            'email' => $role . '@example.test',
            'phone' => '081234567890',
            'password_hash' => password_hash('Rahasia123!', PASSWORD_DEFAULT),
            'role' => $role,
            'is_active' => $active ? 1 : 0,
        ];
    }
}
