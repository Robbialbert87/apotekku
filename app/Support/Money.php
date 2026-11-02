<?php

namespace App\Support;

/**
 * Helper aritmetika uang berbasis sen (integer) agar tidak memakai float
 * untuk perbandingan nominal.
 */
final class Money
{
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return $value * 100;
        }

        if (is_float($value)) {
            return (int) round($value * 100);
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';
        $negative = str_starts_with($clean, '-');
        $clean = ltrim($clean, '-');

        if ($clean === '') {
            return 0;
        }

        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (substr_count($clean, '.') > 1) {
            $clean = str_replace('.', '', $clean);
        } elseif (preg_match('/\.\d{1,2}$/', $clean) !== 1) {
            // Titik tanpa 1-2 digit di belakang dianggap pemisah ribuan.
            $clean = str_replace('.', '', $clean);
        }

        $cents = (int) round(((float) $clean) * 100);

        return $negative ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function toDecimal(string|int|float|null $value): string
    {
        return self::fromCents(self::toCents($value));
    }

    public static function compare(string|int|float|null $left, string|int|float|null $right): int
    {
        return self::toCents($left) <=> self::toCents($right);
    }
}
