<?php

namespace App\Enum;

use App\Entity\Vehicle;
use App\Enum\VehicleUsageType;

/**
 * type de dossier (achat ou location)
 * centralise certaines règles métier liées au véhicule
 */
enum DossierType: string
{
    case PURCHASE = 'purchase';
    case RENTAL = 'rental';

    /**
     * libellé affiché en interface
     */
    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'achat',
            self::RENTAL => 'location',
        };
    }

    /**
     * choix pour les formulaires symfony
     */
    public static function choices(): array
    {
        return [
            'achat' => self::PURCHASE,
            'location' => self::RENTAL,
        ];
    }

    /**
     * action métier lors de la création du dossier
     * applique un état initial au véhicule
     */
    public function applyVehicleOnSubmit(Vehicle $vehicle): void
    {
        $vehicle->reserve();
    }

    /**
     * action métier lors de la validation du dossier
     * met à jour l’état du véhicule selon le type de dossier
     */
    public function applyVehicleValidation(Vehicle $vehicle): void
    {
        match ($this) {
            self::PURCHASE => $vehicle->markAsSold(),
            self::RENTAL => $vehicle->markAsRented(),
        };
    }

    /**
     * action métier lors du refus du dossier
     * remet le véhicule disponible
     */
    public function applyVehicleRejection(Vehicle $vehicle): void
    {
        $vehicle->makeAvailable();
    }

    /**
     * vérifie si le dossier est de type achat
     */
    public function isPurchase(): bool
    {
        return $this === self::PURCHASE;
    }

    /**
     * vérifie si le dossier est de type location
     */
    public function isRental(): bool
    {
        return $this === self::RENTAL;
    }

    /**
     * retourne les types de dossier autorisés selon l’usage du véhicule
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
     * vérifie si un type de dossier est autorisé pour un usage véhicule
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

    /**
     * couleur badge UI (bootstrap ou custom)
     */
    public function badge(): string
    {
        return match ($this) {
            self::PURCHASE => 'primary',
            self::RENTAL => 'info',
        };
    }

    /**
     * icône fontawesome associée
     */
    public function icon(): string
    {
        return match ($this) {
            self::PURCHASE => 'fa-cart-shopping',
            self::RENTAL => 'fa-key',
        };
    }
}
