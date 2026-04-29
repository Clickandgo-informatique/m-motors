<?php

namespace App\Enum;

/**
 * Statuts métier d’un véhicule dans le cycle complet
 */
enum VehicleStatus: string
{
    // =========================================================
    // DISPONIBILITÉ STOCK
    // =========================================================

    /**
     * Disponible à la vente
     */
    case AVAILABLE_FOR_SALE = 'available_sale';

    /**
     * Disponible à la location
     */
    case AVAILABLE_FOR_RENT = 'available_rent';

    /**
     * Véhicule réservé
     */
    case RESERVED = 'reserved';

        // =========================================================
        // EXPLOITATION
        // =========================================================

    case RENTED = 'rented';
    case SOLD = 'sold';

        // =========================================================
        // LOGISTIQUE
        // =========================================================

    case ORDERED = 'ordered';
    case MAINTENANCE = 'maintenance';

    // =========================================================
    // LABELS
    // =========================================================

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE_FOR_SALE => 'Disponible (vente)',
            self::AVAILABLE_FOR_RENT => 'Disponible (location)',
            self::RESERVED => 'Réservé',
            self::RENTED => 'Loué',
            self::SOLD => 'Vendu',
            self::ORDERED => 'Commandé',
            self::MAINTENANCE => 'En maintenance',
        };
    }

    // =========================================================
    // HELPERS MÉTIER
    // =========================================================

    public function isAvailable(): bool
    {
        return in_array($this, [
            self::AVAILABLE_FOR_SALE,
            self::AVAILABLE_FOR_RENT
        ], true);
    }

    public function isAvailableForSale(): bool
    {
        return $this === self::AVAILABLE_FOR_SALE;
    }

    public function isAvailableForRent(): bool
    {
        return $this === self::AVAILABLE_FOR_RENT;
    }

    public function isReserved(): bool
    {
        return $this === self::RESERVED;
    }

    public function isInUse(): bool
    {
        return $this === self::RENTED;
    }

    public function isFinal(): bool
    {
        return $this === self::SOLD;
    }

    public function isUnavailable(): bool
    {
        return !$this->isAvailable();
    }

    public function isVisible(): bool
    {
        return in_array($this, [
            self::AVAILABLE_FOR_SALE,
            self::AVAILABLE_FOR_RENT,
            self::RESERVED
        ], true);
    }

    // =========================================================
    // TRANSITIONS
    // =========================================================

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {

            self::AVAILABLE_FOR_SALE => in_array($target, [
                self::RESERVED,
                self::MAINTENANCE,
            ], true),

            self::AVAILABLE_FOR_RENT => in_array($target, [
                self::RESERVED,
                self::MAINTENANCE,
                self::RENTED,
            ], true),

            self::RESERVED => in_array($target, [
                self::AVAILABLE_FOR_SALE,
                self::AVAILABLE_FOR_RENT,
                self::SOLD,
                self::RENTED
            ], true),

            self::ORDERED => in_array($target, [
                self::AVAILABLE_FOR_SALE,
                self::AVAILABLE_FOR_RENT
            ], true),

            self::MAINTENANCE => in_array($target, [
                self::AVAILABLE_FOR_SALE,
                self::AVAILABLE_FOR_RENT
            ], true),

            self::RENTED => $target === self::AVAILABLE_FOR_RENT,
            self::SOLD => false,
        };
    }

    // =========================================================
    // UI
    // =========================================================

    public function badge(): string
    {
        return match ($this) {
            self::AVAILABLE_FOR_SALE => 'success',
            self::AVAILABLE_FOR_RENT => 'primary',
            self::RESERVED => 'warning text-dark',
            self::RENTED => 'info',
            self::SOLD => 'dark',
            self::ORDERED => 'secondary',
            self::MAINTENANCE => 'danger',
        };
    }

    public static function choices(): array
    {
        return [
            'Disponible (vente)' => self::AVAILABLE_FOR_SALE,
            'Disponible (location)' => self::AVAILABLE_FOR_RENT,
            'Réservé' => self::RESERVED,
            'Loué' => self::RENTED,
            'Vendu' => self::SOLD,
            'Commandé fournisseur' => self::ORDERED,
            'En maintenance' => self::MAINTENANCE,
        ];
    }
}
