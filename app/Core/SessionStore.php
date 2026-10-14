<?php

declare(strict_types=1);

namespace App\Core;

interface SessionStore
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    public function regenerate(): void;

    public function invalidate(): void;

    public function flash(string $key, string $message): void;

    public function pullFlash(string $key): ?string;
}
