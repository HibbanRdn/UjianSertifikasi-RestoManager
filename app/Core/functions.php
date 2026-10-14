<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('rupiah')) {
    function rupiah(int $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }
}
