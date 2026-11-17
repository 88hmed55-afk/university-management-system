<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Decimal money arithmetic using BCMath — never floats (docs/06: DECIMAL + currency code).
 * Scale 2 is a storage precision, not a fee/tax policy (P-012/P-013).
 */
final class MoneyMath
{
    private const SCALE = 2;

    public static function add(string $a, string $b): string
    {
        return bcadd(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function subtract(string $a, string $b): string
    {
        return bcsub(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && is_numeric($value);
    }

    /** @return non-negative numeric string or null */
    public static function nonNegative(?string $value, string $field): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (! is_numeric($value) || bccomp(self::normalize($value), '0', self::SCALE) < 0) {
            throw new InvalidArgumentException("{$field} يجب أن يكون قيمة عددية غير سالبة.");
        }

        return self::normalize($value);
    }

    private static function normalize(string $value): string
    {
        $value = trim($value);

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('قيمة مالية غير صالحة.');
        }

        if (stripos($value, 'e') !== false) {
            return sprintf('%.6F', (float) $value);
        }

        return $value;
    }
}
