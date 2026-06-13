<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case MIDTRANS = 'midtrans';
    case CUSTOMER_PORTAL = 'customer-portal';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::EXTERNAL => 'Eksternal',
            self::MIDTRANS => 'Midtrans (Online)',
            self::CUSTOMER_PORTAL => 'Customer Portal',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}