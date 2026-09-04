<?php
declare(strict_types=1);

namespace Unwinded\Support;

final class Str
{
    /** Generate a URL-safe slug from any string. */
    public static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
        $value = preg_replace('/[\s-]+/', '-', $value);
        return trim($value, '-');
    }

    /** Sanitise a filename: strip path separators and unsafe characters. */
    public static function filename(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        // Prevent double-extension attacks by keeping only the last extension.
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/\./', '', $base);
        return $ext ? $base . '.' . $ext : $base;
    }

    /** Generate a random hex string of $bytes length. */
    public static function random(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /** Truncate to $max characters, appending $ellipsis. */
    public static function truncate(string $value, int $max, string $ellipsis = '…'): string
    {
        if (mb_strlen($value) <= $max) return $value;
        return mb_substr($value, 0, $max - mb_strlen($ellipsis)) . $ellipsis;
    }

    /** Check whether a string starts with the given prefix. */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }
}
