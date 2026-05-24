<?php

namespace App\Enum;

/**
 * statuts métier d’un dossier véhicule
 *
 * représente l’ensemble du cycle de vie :
 * création, validation, paiement, livraison, clôture
 */
enum DossierStatus: string
{
    // initialisation
    case CREATED = 'created';
    case IN_PROGRESS = 'in_progress';

        // validation admin
    case PENDING_VALIDATION = 'pending_validation';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';

        // paiement
    case PENDING_PAYMENT = 'pending_payment';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';

        // finalisation
    case READY_FOR_DELIVERY = 'ready_for_delivery';
    case DELIVERED = 'delivered';
    case CLOSED = 'closed';

    /**
     * libellé affiché en interface
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'créé',
            self::IN_PROGRESS => 'en cours',

            self::PENDING_VALIDATION => 'en attente de validation',
            self::VALIDATED => 'validé',
            self::REJECTED => 'refusé',

            self::PENDING_PAYMENT => 'en attente de paiement',
            self::PARTIALLY_PAID => 'paiement partiel',
            self::PAID => 'payé',

            self::READY_FOR_DELIVERY => 'prêt à livrer',
            self::DELIVERED => 'livré',
            self::CLOSED => 'clôturé',
        };
    }

    /**
     * icône fontawesome associée au statut
     */
    public function icon(): string
    {
        return match ($this) {
            self::CREATED => 'fa-solid fa-file-circle-plus',
            self::IN_PROGRESS => 'fa-solid fa-spinner',

            self::PENDING_VALIDATION => 'fa-solid fa-hourglass-half',
            self::VALIDATED => 'fa-solid fa-check',
            self::REJECTED => 'fa-solid fa-xmark',

            self::PENDING_PAYMENT => 'fa-solid fa-credit-card',
            self::PARTIALLY_PAID => 'fa-solid fa-coins',
            self::PAID => 'fa-solid fa-circle-check',

            self::READY_FOR_DELIVERY => 'fa-solid fa-truck',
            self::DELIVERED => 'fa-solid fa-flag-checkered',
            self::CLOSED => 'fa-solid fa-lock',
        };
    }

    /**
     * badge ui bootstrap associé au statut
     */
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

    /**
     * indique si le dossier est actif
     */
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

    /**
     * indique si le dossier est finalisé
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::CLOSED,
        ], true);
    }

    /**
     * indique si le dossier est bloqué
     */
    public function isBlocked(): bool
    {
        return in_array($this, [
            self::REJECTED,
            self::PENDING_PAYMENT,
        ], true);
    }

    /**
     * indique si le dossier est payé (totalement ou partiellement)
     */
    public function isPaid(): bool
    {
        return in_array($this, [
            self::PARTIALLY_PAID,
            self::PAID,
        ], true);
    }

    /**
     * vérifie si une transition est possible vers un autre statut
     */
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
