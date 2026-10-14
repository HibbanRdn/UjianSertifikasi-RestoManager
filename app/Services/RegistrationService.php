<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\UserRepositoryInterface;
use PDOException;

final class RegistrationService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /** @param array<string, string> $input @return array<string, mixed> */
    public function register(array $input): array
    {
        $name = trim($input['name'] ?? '');
        $email = strtolower(trim($input['email'] ?? ''));
        $phone = $this->normalizePhone($input['phone'] ?? '');
        $password = $input['password'] ?? '';
        $confirmation = $input['password_confirmation'] ?? '';

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Nama lengkap harus terdiri dari 2–100 karakter.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'Masukkan alamat email yang valid.';
        } elseif ($this->users->findByEmail($email) !== null) {
            $errors['email'] = 'Email sudah terdaftar. Silakan masuk menggunakan akun tersebut.';
        }
        if (!preg_match('/^\\+?[0-9]{8,20}$/', $phone)) {
            $errors['phone'] = 'Nomor telepon harus berisi 8–20 digit dan boleh diawali tanda +.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'Password harus terdiri dari 8–72 karakter.';
        } elseif ($password !== $confirmation) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            return $this->users->createCustomer([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ValidationException(['email' => 'Email sudah terdaftar. Silakan masuk.']);
            }

            throw $exception;
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[\\s().-]+/', '', trim($phone)) ?? '';
    }
}
