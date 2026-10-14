<?php

declare(strict_types=1);

namespace App\Repositories;

interface UserRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    /** @param array{name:string,email:string,phone:string,password_hash:string} $data @return array<string, mixed> */
    public function createCustomer(array $data): array;
}
