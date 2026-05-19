<?php

namespace App\Enum;

/**
 * Statuts métier d’un véhicule dans le cycle complet
 */
enum VehicleStatus: string
{
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

    /**
     * Véhicule loué
     */
    case RENTED = 'rented';

    /**
     * Véhicule vendu
     */
    case SOLD = 'sold';

    /**
     * Véhicule commandé
     */
    case ORDERED = 'ordered';

    /**
     * Véhicule en maintenance
     */
    case MAINTENANCE = 'maintenance';

    /**
     * Libellé UI du statut
     */
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

    /**
     * Retourne true si le véhicule est disponible (vente ou location)
     */
    public function isAvailable(): bool
    {
        return in_array($this, [
            self::AVAILABLE_FOR_SALE,
            self::AVAILABLE_FOR_RENT,
        ], true);
    }

    /**
     * Retourne true si le véhicule est réservé
     */
    public function isReserved(): bool
    {
        return $this === self::RESERVED;
    }

    /**
     * Retourne true si le véhicule est vendu
     */
    public function isSold(): bool
    {
        return $this === self::SOLD;
    }

    /**
     * Retourne true si le véhicule est loué
     */
    public function isRented(): bool
    {
        return $this === self::RENTED;
    }

    /**
     * Retourne true si le véhicule est visible dans le catalogue
     */
    public function isVisible(): bool
    {
        return in_array($this, [
            self::AVAILABLE_FOR_SALE,
            self::AVAILABLE_FOR_RENT,
            self::RESERVED,
        ], true);
    }

    /**
     * Vérifie si une transition est autorisée
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {

            self::AVAILABLE_FOR_SALE => in_array($target, [
                self::RESERVED,
                self::MAINTENANCE,
                self::SOLD,
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
                self::RENTED,
            ], true),

            self::ORDERED => in_array($target, [
                self::AVAILABLE_FOR_SALE,
                self::AVAILABLE_FOR_RENT,
            ], true),

            self::MAINTENANCE => in_array($target, [
                self::AVAILABLE_FOR_SALE,
                self::AVAILABLE_FOR_RENT,
            ], true),

            self::RENTED => $target === self::AVAILABLE_FOR_RENT,

            self::SOLD => false,
        };
    }

    /**
     * Retourne la classe CSS du badge UI
     */
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

    /**
     * Passe le véhicule en état réservé
     */
    public function reserve(): self
    {
        return self::RESERVED;
    }

    /**
     * Passe le véhicule en état vendu
     */
    public function markAsSold(): self
    {
        return self::SOLD;
    }

    /**
     * Passe le véhicule en état loué
     */
    public function markAsRented(): self
    {
        return self::RENTED;
    }

    /**
     * Remet le véhicule en disponibilité selon son contexte métier
     */
    public function makeAvailable(): self
    {
        return match ($this) {
            self::AVAILABLE_FOR_RENT,
            self::RENTED => self::AVAILABLE_FOR_RENT,

            default => self::AVAILABLE_FOR_SALE,
        };
    }
}
