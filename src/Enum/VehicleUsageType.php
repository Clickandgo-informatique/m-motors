<?php

namespace App\Enum;

enum VehicleUsageType: string
{
    case SALE = 'sale';
    case RENT = 'rent';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::SALE => 'Achat',
            self::RENT => 'Location',
            self::BOTH => 'Achat & Location',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SALE => 'fa-solid fa-cart-shopping',
            self::RENT => 'fa-solid fa-key',
            self::BOTH => 'fa-solid fa-arrows-left-right',
        };
    }
}
