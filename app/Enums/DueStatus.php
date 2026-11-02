<?php

namespace App\Enums;

enum DueStatus: string
{
    case Terlambat = 'overdue';
    case JatuhTempoHariIni = 'due_today';
    case SegeraJatuhTempo = 'due_soon';
    case BelumJatuhTempo = 'not_due';

    public function label(): string
    {
        return match ($this) {
            self::Terlambat => 'Terlambat',
            self::JatuhTempoHariIni => 'Jatuh Tempo Hari Ini',
            self::SegeraJatuhTempo => 'Segera Jatuh Tempo',
            self::BelumJatuhTempo => 'Belum Jatuh Tempo',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Terlambat => 'badge-danger',
            self::JatuhTempoHariIni => 'badge-warning',
            self::SegeraJatuhTempo => 'badge-info',
            self::BelumJatuhTempo => 'badge-neutral',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
