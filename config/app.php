<?php

declare(strict_types=1);

use App\Core\Environment;

return [
    'environment' => Environment::get('APP_ENV', 'local'),
    'debug' => filter_var(Environment::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'timezone' => Environment::get('APP_TIMEZONE', 'Asia/Jakarta'),
    'url' => Environment::get('APP_URL', 'http://127.0.0.1:8080'),
    'order_estimate_min' => 15,
    'order_estimate_max' => 25,
    'menu_upload' => [
        'directory' => dirname(__DIR__) . '/public/uploads/menu',
        'public_prefix' => '/uploads/menu',
        'max_bytes' => 5 * 1024 * 1024,
        'max_dimension' => 6000,
    ],
];
