<?php
declare(strict_types=1);

namespace Unwinded\Support;

/**
 * Cryptographically secure token generation and verification.
 * Tokens are always stored as a SHA-256 hash, never as plaintext.
 * The raw token is generated once and placed in a URL or email.
 */
final class Token
{
    /**
     * Generate a new token. Returns ['raw' => '...', 'hash' => '...'].
     * Store the hash; send the raw token to the user.
     */
    public static function generate(): array
    {
        $raw  = bin2hex(random_bytes(32));
        return ['raw' => $raw, 'hash' => self::hash($raw)];
    }

    public static function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /** Constant-time comparison of a raw token against a stored hash. */
    public static function verify(string $raw, string $storedHash): bool
    {
        return hash_equals($storedHash, self::hash($raw));
    }
}
