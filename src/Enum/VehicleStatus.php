<?php

namespace App\Enum;

/**
 * Statuts métier d’un véhicule dans le cycle complet :
 * stock, réservation, exploitation, logistique et sortie définitive.
 */
enum VehicleStatus: string
{
    // =========================================================
    // DISPONIBILITÉ STOCK
    // =========================================================

    /**
     * Véhicule disponible dans le catalogue (vente / location possible)
     */
    case AVAILABLE = 'available';

    /**
     * Véhicule réservé par un dossier client (bloqué temporairement)
     */
    case RESERVED = 'reserved';

    // =========================================================
    // EXPLOITATION (UTILISATION DU VÉHICULE)
    // =========================================================

    /**
     * Véhicule actuellement en location
     */
    case RENTED = 'rented';

    /**
     * Véhicule vendu définitivement
     */
    case SOLD = 'sold';

    // =========================================================
    // LOGISTIQUE / FOURNISSEUR
    // =========================================================

    /**
     * Véhicule commandé chez le fournisseur (non encore livré)
     */
    case ORDERED = 'ordered';

    /**
     * Véhicule temporairement indisponible (maintenance atelier)
     */
    case MAINTENANCE = 'maintenance';

    // =========================================================
    // LABELS (AFFICHAGE)
    // =========================================================

    /**
     * Libellé lisible pour l’UI
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponible',
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

    /**
     * Véhicule disponible pour action commerciale
     */
    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    /**
     * Véhicule bloqué par un dossier ou une action client
     */
    public function isReserved(): bool
    {
        return $this === self::RESERVED;
    }

    /**
     * Véhicule en utilisation (location en cours)
     */
    public function isInUse(): bool
    {
        return in_array($this, [
            self::RENTED
        ], true);
    }

    /**
     * Véhicule définitivement sorti du stock (vente)
     */
    public function isFinal(): bool
    {
        return $this === self::SOLD;
    }

    /**
     * Véhicule indisponible globalement
     */
    public function isUnavailable(): bool
    {
        return in_array($this, [
            self::RESERVED,
            self::RENTED,
            self::SOLD,
            self::ORDERED,
            self::MAINTENANCE
        ], true);
    }

    /**
     * Véhicule visible dans le catalogue public
     */
    public function isVisible(): bool
    {
        return in_array($this, [
            self::AVAILABLE,
            self::RESERVED
        ], true);
    }

    // =========================================================
    // TRANSITIONS D’ÉTAT
    // =========================================================

    /**
     * Vérifie si une transition de statut est autorisée
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {

            self::AVAILABLE => in_array($target, [
                self::RESERVED,
                self::MAINTENANCE
            ], true),

            self::RESERVED => in_array($target, [
                self::AVAILABLE,
                self::SOLD,
                self::RENTED
            ], true),

            self::ORDERED => $target === self::AVAILABLE,

            self::MAINTENANCE => $target === self::AVAILABLE,

            self::SOLD => false,
            self::RENTED => false,
        };
    }

    // =========================================================
    // UI / AFFICHAGE
    // =========================================================

    /**
     * Classe CSS pour badge UI
     */
    public function badge(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::RESERVED => 'warning text-dark',
            self::RENTED => 'info',
            self::SOLD => 'dark',
            self::ORDERED => 'primary',
            self::MAINTENANCE => 'secondary',
        };
    }

    /**
     * Options pour formulaires Symfony
     */
    public static function choices(): array
    {
        return [
            'Disponible' => self::AVAILABLE,
            'Réservé' => self::RESERVED,
            'Loué' => self::RENTED,
            'Vendu' => self::SOLD,
            'Commandé fournisseur' => self::ORDERED,
            'En maintenance' => self::MAINTENANCE,
        ];
    }
}
