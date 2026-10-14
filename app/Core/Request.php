<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @param array<string, mixed> $query @param array<string, mixed> $body @param array<string, mixed> $server @param array<string, mixed> $files */
    public function __construct(
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $files = []
    ) {
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $path = parse_url((string) ($this->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $normalized = '/' . ltrim((string) $path, '/');

        return $normalized !== '/' ? rtrim($normalized, '/') : '/';
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }
}
