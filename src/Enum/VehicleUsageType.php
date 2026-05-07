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
            self::SALE => 'Vente',
            self::RENT => 'Location',
            self::BOTH => 'Vente & Location',
        };
    }
}
