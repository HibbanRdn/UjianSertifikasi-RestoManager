<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly SessionStore $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function verify(string $submittedToken): bool
    {
        $stored = $this->session->get(self::SESSION_KEY);

        return is_string($stored)
            && $submittedToken !== ''
            && hash_equals($stored, $submittedToken);
    }
}
