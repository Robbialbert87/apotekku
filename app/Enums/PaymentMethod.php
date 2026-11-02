<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'transfer';
    case Tunai = 'tunai';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Transfer',
            self::Tunai => 'Tunai',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $method): array => [$method->value => $method->label()])
            ->all();
    }
}
