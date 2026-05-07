<?php

namespace App\Enum;

/**
 * Statuts métier d’un dossier véhicule
 *
 * Représente l’avancement complet :
 * - création
 * - validation
 * - paiement
 * - livraison
 */
enum DossierStatus: string
{
    // =========================================================
    // INITIALISATION
    // =========================================================

    case CREATED = 'created';

    case IN_PROGRESS = 'in_progress';

        // =========================================================
        // VALIDATION ADMIN
        // =========================================================

    case PENDING_VALIDATION = 'pending_validation';

    case VALIDATED = 'validated';

    case REJECTED = 'rejected';

        // =========================================================
        // PAIEMENT
        // =========================================================

    case PENDING_PAYMENT = 'pending_payment';

    case PARTIALLY_PAID = 'partially_paid';

    case PAID = 'paid';

        // =========================================================
        // FINALISATION
        // =========================================================

    case READY_FOR_DELIVERY = 'ready_for_delivery';

    case DELIVERED = 'delivered';

    case CLOSED = 'closed';

    // =========================================================
    // LABEL UI
    // =========================================================

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Créé',
            self::IN_PROGRESS => 'En cours',

            self::PENDING_VALIDATION => 'En attente validation',
            self::VALIDATED => 'Validé',
            self::REJECTED => 'Refusé',

            self::PENDING_PAYMENT => 'En attente paiement',
            self::PARTIALLY_PAID => 'Paiement partiel',
            self::PAID => 'Payé',

            self::READY_FOR_DELIVERY => 'Prêt à livrer',
            self::DELIVERED => 'Livré',
            self::CLOSED => 'Clôturé',
        };
    }

    // =========================================================
    // LOGIQUE MÉTIER
    // =========================================================

    public function isActive(): bool
    {
        return in_array($this, [
            self::CREATED,
            self::IN_PROGRESS,
            self::PENDING_VALIDATION,
            self::VALIDATED,
            self::PENDING_PAYMENT,
            self::PARTIALLY_PAID,
            self::PAID,
            self::READY_FOR_DELIVERY,
        ], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::CLOSED,
        ], true);
    }

    public function isBlocked(): bool
    {
        return in_array($this, [
            self::REJECTED,
            self::PENDING_PAYMENT,
        ], true);
    }

    public function isPaid(): bool
    {
        return in_array($this, [
            self::PARTIALLY_PAID,
            self::PAID,
        ], true);
    }

    // =========================================================
    // UI (BADGES)
    // =========================================================

    public function badge(): string
    {
        return match ($this) {
            self::CREATED => 'secondary',
            self::IN_PROGRESS => 'info',

            self::PENDING_VALIDATION => 'warning',
            self::VALIDATED => 'primary',
            self::REJECTED => 'danger',

            self::PENDING_PAYMENT => 'warning',
            self::PARTIALLY_PAID => 'info',
            self::PAID => 'success',

            self::READY_FOR_DELIVERY => 'primary',
            self::DELIVERED => 'success',
            self::CLOSED => 'dark',
        };
    }

    // =========================================================
    // TRANSITIONS SIMPLES (OPTIONNEL MAIS UTILE PFE)
    // =========================================================

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {

            self::CREATED => in_array($target, [
                self::IN_PROGRESS,
                self::PENDING_VALIDATION,
            ], true),

            self::IN_PROGRESS => in_array($target, [
                self::PENDING_VALIDATION,
                self::REJECTED,
            ], true),

            self::PENDING_VALIDATION => in_array($target, [
                self::VALIDATED,
                self::REJECTED,
            ], true),

            self::VALIDATED => in_array($target, [
                self::PENDING_PAYMENT,
            ], true),

            self::PENDING_PAYMENT => in_array($target, [
                self::PARTIALLY_PAID,
                self::PAID,
            ], true),

            self::PARTIALLY_PAID => in_array($target, [
                self::PAID,
            ], true),

            self::PAID => in_array($target, [
                self::READY_FOR_DELIVERY,
            ], true),

            self::READY_FOR_DELIVERY => in_array($target, [
                self::DELIVERED,
            ], true),

            self::DELIVERED => in_array($target, [
                self::CLOSED,
            ], true),

            self::CLOSED => false,

            self::REJECTED => false,
        };
    }
}
