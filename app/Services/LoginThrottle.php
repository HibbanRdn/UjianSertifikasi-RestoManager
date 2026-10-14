<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\SessionStore;

final class LoginThrottle
{
    private const KEY = 'auth.login_attempts';

    public function __construct(
        private readonly SessionStore $session,
        private readonly int $maxAttempts = 5,
        private readonly int $decaySeconds = 300
    ) {
    }

    public function retryAfterSeconds(): int
    {
        $state = $this->state();
        if ($state['count'] < $this->maxAttempts) {
            return 0;
        }

        return max(0, $this->decaySeconds - (time() - $state['started_at']));
    }

    public function recordFailure(): void
    {
        $state = $this->state();
        $state['count']++;
        $this->session->put(self::KEY, $state);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    /** @return array{count:int,started_at:int} */
    private function state(): array
    {
        $state = $this->session->get(self::KEY);
        if (!is_array($state) || !isset($state['count'], $state['started_at']) || time() - (int) $state['started_at'] >= $this->decaySeconds) {
            return ['count' => 0, 'started_at' => time()];
        }

        return ['count' => (int) $state['count'], 'started_at' => (int) $state['started_at']];
    }
}
