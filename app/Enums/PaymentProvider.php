<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::EXTERNAL => 'Eksternal',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}