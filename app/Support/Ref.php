<?php
declare(strict_types=1);

namespace Unwinded\Support;

/**
 * Public reference generator.
 * Produces human-dictatable references like "UNW-B-7F3K9XQ2TB"
 * using Crockford Base32 (excludes I, L, O, U to avoid confusion).
 */
final class Ref
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const LENGTH   = 10;

    public static function generate(string $prefix): string
    {
        $bytes  = random_bytes((int) ceil(self::LENGTH * 5 / 8) + 1);
        $number = gmp_import($bytes, 1, GMP_MSW_FIRST | GMP_NATIVE_ENDIAN);
        $result = '';
        $base   = gmp_init(32);
        for ($i = 0; $i < self::LENGTH; $i++) {
            [$number, $remainder] = gmp_div_qr($number, $base);
            $result = self::ALPHABET[(int) gmp_strval($remainder)] . $result;
        }
        return strtoupper($prefix) . '-' . $result;
    }
}
