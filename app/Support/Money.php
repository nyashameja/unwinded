<?php
declare(strict_types=1);

namespace Unwinded\Support;

/**
 * Immutable money value object. Stores integer cents.
 * All arithmetic uses cents to avoid floating-point errors.
 * Currency is always ZAR for this application.
 */
final class Money
{
    private function __construct(private readonly int $cents) {}

    public static function ofCents(int $cents): self
    {
        return new self($cents);
    }

    /** Create from a rand amount (e.g. "1234.56" → 123456 cents). */
    public static function ofRand(string|float|int $rand): self
    {
        // Use string arithmetic to avoid floating-point rounding.
        $str   = number_format((float) $rand, 2, '.', '');
        $parts = explode('.', $str);
        $cents = (int) $parts[0] * 100 + (int) ($parts[1] ?? 0);
        return new self($cents);
    }

    /** Create from a DECIMAL value stored in the database as a string. */
    public static function fromDb(string $value): self
    {
        return self::ofRand($value);
    }

    public function cents(): int { return $this->cents; }

    /** Return the value as a string suitable for DECIMAL database storage. */
    public function toDb(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(Money $other): self
    {
        return new self($this->cents - $other->cents);
    }

    /** Multiply by a scalar (e.g. quantity or percentage); rounds half-up. */
    public function multiply(int|float|string $factor): self
    {
        return new self((int) round($this->cents * (float) $factor, 0, PHP_ROUND_HALF_UP));
    }

    /** Apply a percentage discount (0–100). */
    public function discountPercent(int|float $percent): self
    {
        $discount = (int) round($this->cents * $percent / 100, 0, PHP_ROUND_HALF_UP);
        return new self(max(0, $this->cents - $discount));
    }

    /** Apply a fixed discount, flooring to zero. */
    public function discountFixed(Money $discount): self
    {
        return new self(max(0, $this->cents - $discount->cents));
    }

    /**
     * Distribute this amount into N parts as precisely as possible.
     * The remainder cents are distributed to the first parts.
     * Result: array of Money, sum == $this.
     */
    public function allocate(int $parts): array
    {
        if ($parts <= 0) throw new \InvalidArgumentException('Parts must be > 0');
        $base      = intdiv($this->cents, $parts);
        $remainder = $this->cents % $parts;
        $result    = [];
        for ($i = 0; $i < $parts; $i++) {
            $result[] = new self($base + ($i < $remainder ? 1 : 0));
        }
        return $result;
    }

    public function isZero(): bool     { return $this->cents === 0; }
    public function isNegative(): bool { return $this->cents < 0; }
    public function isPositive(): bool { return $this->cents > 0; }

    public function greaterThan(Money $other): bool  { return $this->cents > $other->cents; }
    public function lessThan(Money $other): bool     { return $this->cents < $other->cents; }
    public function equals(Money $other): bool       { return $this->cents === $other->cents; }

    public static function zero(): self { return new self(0); }
    public static function max(Money ...$amounts): self { return new self(max(array_map(fn($m) => $m->cents, $amounts))); }
    public static function min(Money ...$amounts): self { return new self(min(array_map(fn($m) => $m->cents, $amounts))); }

    public static function sum(Money ...$amounts): self
    {
        return new self(array_sum(array_map(fn($m) => $m->cents, $amounts)));
    }

    /** Format as "R 1 234.56" with non-breaking thin space. */
    public function format(bool $showCents = true): string
    {
        $rand = $this->cents / 100;
        return 'R\u{202F}' . number_format($rand, $showCents ? 2 : 0, '.', '\u{202F}');
    }

    public function __toString(): string { return $this->format(); }
}
