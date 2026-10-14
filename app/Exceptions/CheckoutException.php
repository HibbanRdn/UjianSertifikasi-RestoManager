<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class CheckoutException extends RuntimeException
{
    public function __construct(string $message, private readonly string $reason = 'invalid_checkout')
    {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
