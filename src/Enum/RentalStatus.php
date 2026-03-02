<?php

namespace App\Enum;

enum RentalStatus: string
{
    case CREATED   = 'created';
    case CONFIRMED = 'confirmed';
    case ACTIVE    = 'active';
    case FINISHED  = 'finished';
    case CANCELED  = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::CREATED   => 'Créée',
            self::CONFIRMED => 'Confirmée',
            self::ACTIVE    => 'En cours',
            self::FINISHED  => 'Terminée',
            self::CANCELED  => 'Annulée',
        };
    }
}
