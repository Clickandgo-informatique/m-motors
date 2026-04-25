<?php

namespace App\Enum;

use App\Entity\Vehicle;

enum DossierType: string
{
    case SALE = 'sale';
    case RENTAL = 'rental';

    // =========================================================
    // UI
    // =========================================================

    public function label(): string
    {
        return match ($this) {
            self::SALE => 'Achat',
            self::RENTAL => 'Location',
        };
    }

    public static function choices(): array
    {
        return [
            'Achat' => self::SALE,
            'Location' => self::RENTAL,
        ];
    }

    // =========================================================
    // SOUMISSION (réservation véhicule)
    // =========================================================

    public function applyVehicleOnSubmit(Vehicle $vehicle): void
    {
        // réservation simple pour tout type de dossier
        $vehicle->reserve();
    }

    // =========================================================
    // VALIDATION
    // =========================================================

    public function applyVehicleValidation(Vehicle $vehicle): void
    {
        match ($this) {
            self::SALE => $vehicle->markAsSold(),
            self::RENTAL => $vehicle->markAsRented(),
        };
    }

    // =========================================================
    // REFUS
    // =========================================================

    public function applyVehicleRejection(Vehicle $vehicle): void
    {
        $vehicle->makeAvailable();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    public function isSale(): bool
    {
        return $this === self::SALE;
    }

    public function isRental(): bool
    {
        return $this === self::RENTAL;
    }
}
