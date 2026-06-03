<?php

namespace App\Enum;

enum VehicleStatus: string
{
    case AVAILABLE_FOR_SALE = 'available_sale';
    case AVAILABLE_FOR_RENT = 'available_rent';
    case RESERVED = 'reserved';
    case RENTED = 'rented';
    case SOLD = 'sold';
    case ORDERED = 'ordered';
    case MAINTENANCE = 'maintenance';

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

    public function isAvailable(): bool
    {
        return in_array($this, [
            self::AVAILABLE_FOR_SALE,
            self::AVAILABLE_FOR_RENT,
        ], true);
    }

    public function isReserved(): bool
    {
        return $this === self::RESERVED;
    }

    public function isSold(): bool
    {
        return $this === self::SOLD;
    }

    public function isRented(): bool
    {
        return $this === self::RENTED;
    }

    public function isVisible(): bool
    {
        return in_array($this, [
            self::AVAILABLE_FOR_SALE,
            self::AVAILABLE_FOR_RENT,
            self::RESERVED,
        ], true);
    }

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

    public function reserve(): self
    {
        return self::RESERVED;
    }

    public function markAsSold(): self
    {
        return self::SOLD;
    }

    public function markAsRented(): self
    {
        return self::RENTED;
    }

    public function makeAvailable(): self
    {
        return match ($this) {
            self::AVAILABLE_FOR_RENT,
            self::RENTED => self::AVAILABLE_FOR_RENT,
            default => self::AVAILABLE_FOR_SALE,
        };
    }
}
