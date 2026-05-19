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

    // Retourne les types de dossiers autorisés pour ce mode d'usage véhicule.        
    public function allowedDossierTypes(): array
    {
        return match ($this) {
            self::SALE => [DossierType::PURCHASE],
            self::RENT => [DossierType::RENTAL],
            self::BOTH => [DossierType::PURCHASE, DossierType::RENTAL],
        };
    }
}
