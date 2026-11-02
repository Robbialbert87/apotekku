<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class Format
{
    public static function rupiah(string|int|float|null $amount, bool $withPrefix = true): string
    {
        $cents = Money::toCents($amount);
        $hasCents = $cents % 100 !== 0;
        $value = $cents / 100;

        $formatted = number_format(
            $value,
            $hasCents ? 2 : 0,
            ',',
            '.',
        );

        return $withPrefix ? 'Rp '.$formatted : $formatted;
    }

    public static function tanggal(CarbonInterface|string|null $date, string $format = 'd F Y'): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat($format);
    }

    public static function tanggalWaktu(CarbonInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat('d F Y, H:i');
    }

    public static function fileSize(int|float|null $bytes): string
    {
        $bytes = (float) ($bytes ?? 0);

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return number_format($bytes, 0, ',', '.').' B';
    }
}
