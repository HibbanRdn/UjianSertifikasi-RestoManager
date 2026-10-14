<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\SessionStore;

final class ArraySession implements SessionStore
{
    /** @var array<string, mixed> */
    public array $data = [];
    public int $regenerations = 0;
    public int $invalidations = 0;

    /** @param array<string, mixed> $initial */
    public function __construct(array $initial = [])
    {
        $this->data = $initial;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
        $this->regenerations++;
    }

    public function invalidate(): void
    {
        $this->data = [];
        $this->invalidations++;
        $this->regenerations++;
    }

    public function flash(string $key, string $message): void
    {
        $this->data['_flash'][$key] = $message;
    }

    public function pullFlash(string $key): ?string
    {
        $message = $this->data['_flash'][$key] ?? null;
        unset($this->data['_flash'][$key]);

        return is_string($message) ? $message : null;
    }
}
