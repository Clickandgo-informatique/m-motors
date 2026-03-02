<?php

namespace App\Enum;

enum VehicleStatus: string
{
    case AVAILABLE   = 'available';
    case RENTED      = 'rented';
    case SOLD        = 'sold';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE   => 'Disponible',
            self::RENTED      => 'Loué',
            self::SOLD        => 'Vendu',
            self::MAINTENANCE => 'En maintenance',
        };
    }
}
