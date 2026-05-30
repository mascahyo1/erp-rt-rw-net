<?php

namespace App\Enums;

enum InternalPaymentMethod: string
{
    case TUNAI = 'tunai';
    case TRANSFER_MANUAL = 'transfer_manual';

    public function label(): string
    {
        return match ($this) {
            self::TUNAI => 'Tunai',
            self::TRANSFER_MANUAL => 'Transfer Manual',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}