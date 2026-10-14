<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Exceptions\HttpException;

final class RoleGuard
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly RolePolicy $policy
    ) {
    }

    /** @param list<string> $roles */
    public function requireRoles(array $roles): void
    {
        if (!$this->auth->check()) {
            Response::redirect('/login');
        }

        if (!$this->auth->hasAnyRole($roles)) {
            throw new HttpException(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function requireGuest(): void
    {
        if ($this->auth->check()) {
            Response::redirect($this->auth->redirectPath());
        }
    }

    public function requireCustomer(): void
    {
        $this->requireRoles(['customer']);
    }

    public function requireStaff(): void
    {
        if (!$this->auth->check()) {
            Response::redirect('/login');
        }
        $role = $this->auth->user()['role'] ?? null;
        if (!$this->policy->canAccessStaff(is_string($role) ? $role : null)) {
            throw new HttpException(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function requireAdmin(): void
    {
        $this->requireRoles(['admin']);
    }
}
