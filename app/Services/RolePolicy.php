<?php

declare(strict_types=1);

namespace App\Services;

final class RolePolicy
{
    public function canAccessCustomer(?string $role): bool
    {
        return $role === 'customer';
    }

    public function canAccessStaff(?string $role): bool
    {
        return in_array($role, ['staff', 'admin'], true);
    }

    public function canAccessAdmin(?string $role): bool
    {
        return $role === 'admin';
    }
}
