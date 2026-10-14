<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\LoginThrottle;
use App\Services\RegistrationService;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class AuthenticationDatabaseTest extends TestCase
{
    private PDO $pdo;
    private string $email;

    protected function setUp(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $this->pdo = $app['pdo'];
        $this->email = 'fase1-' . bin2hex(random_bytes(6)) . '@example.test';
    }

    protected function tearDown(): void
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE email = :email');
        $statement->execute(['email' => $this->email]);
    }

    public function testRegistrationLoginAndLogoutAgainstMariaDb(): void
    {
        $repository = new UserRepository($this->pdo);
        $registration = new RegistrationService($repository);
        $user = $registration->register([
            'name' => 'Penguji Integrasi',
            'email' => $this->email,
            'phone' => '+6281234567890',
            'password' => 'Integrasi123!',
            'password_confirmation' => 'Integrasi123!',
            'role' => 'admin',
        ]);

        self::assertSame('customer', $user['role']);
        self::assertTrue(password_verify('Integrasi123!', (string) $user['password_hash']));

        $session = new ArraySession(['customer.cart' => ['stale' => true]]);
        $auth = new AuthService($repository, $session, new LoginThrottle($session));
        self::assertTrue($auth->attempt($this->email, 'Integrasi123!'));
        self::assertSame('customer', $auth->user()['role']);
        self::assertFalse($session->has('customer.cart'));

        $auth->logout();
        self::assertFalse($auth->check());
        self::assertSame([], $session->data);
    }
}
