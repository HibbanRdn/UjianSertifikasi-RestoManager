<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, phone, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => strtolower($email)]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, phone, password_hash, role, is_active FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function createCustomer(array $data): array
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO users (name, email, phone, password_hash, role, is_active)
             VALUES (:name, :email, :phone, :password_hash, 'customer', 1)"
        );
        $statement->execute($data);

        $user = $this->findById((int) $this->pdo->lastInsertId());
        if ($user === null) {
            throw new \RuntimeException('Akun yang baru dibuat tidak dapat dibaca kembali.');
        }

        return $user;
    }
}
