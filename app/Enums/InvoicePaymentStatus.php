<?php

namespace App\Enums;

enum InvoicePaymentStatus: string
{
    case BelumDibayar = 'unpaid';
    case DibayarSebagian = 'partial';
    case Lunas = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::BelumDibayar => 'Belum Dibayar',
            self::DibayarSebagian => 'Dibayar Sebagian',
            self::Lunas => 'Lunas',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BelumDibayar => 'badge-danger',
            self::DibayarSebagian => 'badge-warning',
            self::Lunas => 'badge-success',
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
