<?php
declare(strict_types=1);

namespace Unwinded\Support;

/**
 * Thin time abstraction. All "current time" calls go through here
 * so tests can substitute a fixed clock without monkey-patching.
 */
final class Clock
{
    private static ?DateTimeImmutable $fixed = null;
    private static string $tz = 'Africa/Johannesburg';

    public static function setTimezone(string $tz): void { self::$tz = $tz; }
    public static function freeze(DateTimeImmutable $dt): void { self::$fixed = $dt; }
    public static function unfreeze(): void { self::$fixed = null; }

    public static function now(): DateTimeImmutable
    {
        return self::$fixed ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function local(): DateTimeImmutable
    {
        return self::now()->setTimezone(new DateTimeZone(self::$tz));
    }

    public static function utcString(): string
    {
        return self::now()->format('Y-m-d H:i:s');
    }

    public static function localString(string $format = 'Y-m-d H:i'): string
    {
        return self::local()->format($format);
    }

    public static function displayDate(string $utcDatetime): string
    {
        $dt = new DateTimeImmutable($utcDatetime, new DateTimeZone('UTC'));
        return $dt->setTimezone(new DateTimeZone(self::$tz))->format('d F Y');
    }

    public static function displayDateTime(string $utcDatetime): string
    {
        $dt = new DateTimeImmutable($utcDatetime, new DateTimeZone('UTC'));
        return $dt->setTimezone(new DateTimeZone(self::$tz))->format('d F Y, g:ia');
    }

    /** Add N business days to a UTC datetime, returning a UTC datetime string. */
    public static function addBusinessDays(DateTimeImmutable $from, int $days): DateTimeImmutable
    {
        $dt = $from->setTimezone(new DateTimeZone(self::$tz));
        $added = 0;
        while ($added < $days) {
            $dt = $dt->modify('+1 day');
            $dow = (int) $dt->format('N'); // 1=Mon, 7=Sun
            if ($dow < 6) { // Mon–Fri only (no public-holiday exclusion in v1)
                $added++;
            }
        }
        return $dt->setTimezone(new DateTimeZone('UTC'));
    }
}
