<?php

namespace App\Enums;

enum PaymentRecordStatus: string
{
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Posted => 'Tercatat',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
