<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\SessionStore;
use App\Repositories\UserRepositoryInterface;

final class AuthService
{
    private const USER_ID_KEY = 'auth.user_id';

    /** @var array<string, mixed>|null|false */
    private array|null|false $cachedUser = false;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly SessionStore $session,
        private readonly LoginThrottle $throttle
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        if ($this->throttle->retryAfterSeconds() > 0) {
            return false;
        }

        $user = $this->users->findByEmail(strtolower(trim($email)));
        if ($user === null || (int) $user['is_active'] !== 1 || !password_verify($password, (string) $user['password_hash'])) {
            $this->throttle->recordFailure();

            return false;
        }

        $this->loginUser($user);
        $this->throttle->clear();

        return true;
    }

    /** @param array<string, mixed> $user */
    public function loginUser(array $user): void
    {
        $pendingAdd = $this->session->get('customer.pending_add');
        $this->clearCustomerState();
        if (($user['role'] ?? null) === 'customer' && $pendingAdd !== null) {
            $this->session->put('customer.pending_add', $pendingAdd);
        }

        $this->session->regenerate();
        $this->session->put(self::USER_ID_KEY, (int) $user['id']);
        $this->cachedUser = $user;
    }

    public function logout(): void
    {
        $this->cachedUser = false;
        $this->session->invalidate();
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if ($this->cachedUser !== false) {
            return $this->cachedUser;
        }

        $userId = $this->session->get(self::USER_ID_KEY);
        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            $this->cachedUser = null;

            return null;
        }

        $user = $this->users->findById((int) $userId);
        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->logout();
            $this->cachedUser = null;

            return null;
        }

        $this->cachedUser = $user;

        return $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /** @param list<string> $roles */
    public function hasAnyRole(array $roles): bool
    {
        $user = $this->user();

        return $user !== null && in_array((string) $user['role'], $roles, true);
    }

    public function redirectPath(?string $returnTo = null): string
    {
        $role = (string) ($this->user()['role'] ?? '');
        if ($role === 'admin') {
            return '/admin/dashboard';
        }
        if ($role === 'staff') {
            return '/staff/dashboard';
        }

        if ($this->session->has('customer.pending_add')) {
            return '/tables/select';
        }

        $allowed = ['/cart', '/orders', '/history', '/menu', '/categories', '/profile'];

        return in_array($returnTo, $allowed, true) ? (string) $returnTo : '/menu';
    }

    private function clearCustomerState(): void
    {
        foreach (['customer.cart', 'customer.draft_table', 'customer.selected_table_id', 'customer.checkout_draft', 'customer.pending_add'] as $key) {
            $this->session->forget($key);
        }
        $this->session->forget(self::USER_ID_KEY);
    }
}
