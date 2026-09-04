<?php
declare(strict_types=1);

namespace Unwinded\Core;

class RateLimiter
{
    public function __construct(private Database $db) {}

    /**
     * Returns true if the action is allowed; false if the limit is exceeded.
     * Stores attempts in the rate_limits table.
     */
    public function attempt(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $window = date('Y-m-d H:i:s', strtotime("-{$decayMinutes} minutes"));
        $count  = (int) $this->db->fetchScalar(
            "SELECT COUNT(*) FROM rate_limits WHERE key_hash = ? AND created_at >= ?",
            [hash('sha256', $key), $window]
        );
        if ($count >= $maxAttempts) {
            return false;
        }
        $this->db->insert('rate_limits', [
            'key_hash'   => hash('sha256', $key),
            'created_at' => now(),
        ]);
        return true;
    }

    public function tooManyAttempts(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        return !$this->attempt($key, $maxAttempts, $decayMinutes);
    }
}
