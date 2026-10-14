<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $status = 302): never
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            $path = '/';
        }

        header('Location: ' . $path, true, $status);
        exit;
    }
}
