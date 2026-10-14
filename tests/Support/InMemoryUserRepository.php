<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Repositories\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $users = [];

    /** @param array<int, array<string, mixed>> $users */
    public function __construct(array $users = [])
    {
        foreach ($users as $user) {
            $this->users[(int) $user['id']] = $user;
        }
    }

    public function findByEmail(string $email): ?array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findById(int $id): ?array
    {
        return $this->users[$id] ?? null;
    }

    public function createCustomer(array $data): array
    {
        $id = $this->users === [] ? 1 : max(array_keys($this->users)) + 1;
        $user = [...$data, 'id' => $id, 'role' => 'customer', 'is_active' => 1];
        $this->users[$id] = $user;

        return $user;
    }
}
