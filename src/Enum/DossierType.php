<?php

namespace App\Enum;

use App\Enum\VehicleUsageType;

enum DossierType: string
{
    case PURCHASE = 'purchase';
    case RENTAL = 'rental';

    /**
     * Libellé affiché en UI
     */
    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Achat',
            self::RENTAL => 'Location',
        };
    }

    /**
     * Choix pour formulaires Symfony
     */
    public static function choices(): array
    {
        return [
            'Achat' => self::PURCHASE,
            'Location' => self::RENTAL,
        ];
    }

    /**
     * Action métier lors de la création du dossier
     */
    public function applyVehicleOnSubmit($vehicle): void
    {
        $vehicle->reserve();
    }

    /**
     * Action métier lors de la validation du dossier
     */
    public function applyVehicleValidation($vehicle): void
    {
        match ($this) {
            self::PURCHASE => $vehicle->markAsSold(),
            self::RENTAL => $vehicle->markAsRented(),
        };
    }

    /**
     * Action métier lors du refus du dossier
     */
    public function applyVehicleRejection($vehicle): void
    {
        $vehicle->makeAvailable();
    }

    /**
     * Vérifie si achat
     */
    public function isPurchase(): bool
    {
        return $this === self::PURCHASE;
    }

    /**
     * Vérifie si location
     */
    public function isRental(): bool
    {
        return $this === self::RENTAL;
    }

    /**
     * Retourne les types autorisés selon usage véhicule
     */
    public static function fromVehicleUsageType(VehicleUsageType $usageType): array
    {
        return match ($usageType) {
            VehicleUsageType::SALE => [self::PURCHASE],
            VehicleUsageType::RENT => [self::RENTAL],
            VehicleUsageType::BOTH => [self::PURCHASE, self::RENTAL],
        };
    }

    /**
     * Vérifie si un type est autorisé pour un véhicule
     */
    public static function isAllowedForVehicleUsage(
        self $dossierType,
        VehicleUsageType $usageType
    ): bool {
        return in_array(
            $dossierType,
            self::fromVehicleUsageType($usageType),
            true
        );
    }
}
