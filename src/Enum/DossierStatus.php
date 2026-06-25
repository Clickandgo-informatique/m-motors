<?php

namespace App\Enum;

/**
 * statuts métier du workflow dossier
 *
 * ces valeurs doivent correspondre exactement
 * aux places définies dans dossier_workflow.yaml
 */
enum DossierStatus: string
{
    case DRAFT = 'draft';
    case VEHICLE_SELECTED = 'vehicle_selected';
    case DOCUMENTS_PENDING = 'documents_pending';
    case DOCUMENTS_REVIEW = 'documents_review';
    case FINANCING_REVIEW = 'financing_review';
    case ORDER_SIGNED = 'order_signed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * libellé affiché en interface
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::VEHICLE_SELECTED => 'Véhicule sélectionné',
            self::DOCUMENTS_PENDING => 'Documents à fournir',
            self::DOCUMENTS_REVIEW => 'Documents en validation',
            self::FINANCING_REVIEW => 'Financement en cours',
            self::ORDER_SIGNED => 'Commande signée',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    /**
     * icône fontawesome associée au statut
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'fa-solid fa-file-circle-plus',
            self::VEHICLE_SELECTED => 'fa-solid fa-car',
            self::DOCUMENTS_PENDING => 'fa-solid fa-file-circle-exclamation',
            self::DOCUMENTS_REVIEW => 'fa-solid fa-magnifying-glass',
            self::FINANCING_REVIEW => 'fa-solid fa-money-check-dollar',
            self::ORDER_SIGNED => 'fa-solid fa-signature',
            self::COMPLETED => 'fa-solid fa-circle-check',
            self::CANCELLED => 'fa-solid fa-ban',
        };
    }

    /**
     * badge ui associé au statut
     */
    public function badge(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::VEHICLE_SELECTED => 'info',
            self::DOCUMENTS_PENDING => 'warning',
            self::DOCUMENTS_REVIEW => 'warning',
            self::FINANCING_REVIEW => 'primary',
            self::ORDER_SIGNED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * indique si le dossier est actif
     */
    public function isActive(): bool
    {
        return !in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
        ], true);
    }

    /**
     * indique si le dossier est finalisé
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
        ], true);
    }

    /**
     * indique si le dossier est annulé
     */
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * indique si le dossier est terminé avec succès
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * vérifie si une transition est théoriquement possible
     * selon le workflow métier
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => in_array($target, [
                self::VEHICLE_SELECTED,
                self::CANCELLED,
            ], true),

            self::VEHICLE_SELECTED => in_array($target, [
                self::DOCUMENTS_PENDING,
                self::CANCELLED,
            ], true),

            self::DOCUMENTS_PENDING => in_array($target, [
                self::DOCUMENTS_REVIEW,
                self::CANCELLED,
            ], true),

            self::DOCUMENTS_REVIEW => in_array($target, [
                self::DOCUMENTS_PENDING,
                self::FINANCING_REVIEW,
                self::CANCELLED,
            ], true),

            self::FINANCING_REVIEW => in_array($target, [
                self::DOCUMENTS_REVIEW,
                self::ORDER_SIGNED,
                self::CANCELLED,
            ], true),

            self::ORDER_SIGNED => in_array($target, [
                self::COMPLETED,
                self::CANCELLED,
            ], true),

            self::COMPLETED,
            self::CANCELLED => false,
        };
    }
}
