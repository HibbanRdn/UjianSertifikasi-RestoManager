<?php

declare(strict_types=1);

namespace App\Core;

final class NativeSession implements SessionStore
{
    public static function start(bool $secureCookie = false): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_name('RESTOMANAGERSESSID');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secureCookie,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        return new self();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        $this->regenerate();
    }

    public function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public function pullFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return is_string($message) ? $message : null;
    }
}
