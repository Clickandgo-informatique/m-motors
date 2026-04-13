<?php

namespace App\Enum;

/**
 * Enum représentant les statuts possibles d’un dossier
 */
enum DossierStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Label lisible pour affichage (UI)
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    /**
     * (Optionnel) Couleur Bootstrap / badge
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * (Optionnel) Liste pour formulaires Symfony
     */
    public static function choices(): array
    {
        return [
            'Brouillon' => self::DRAFT,
            'En cours' => self::IN_PROGRESS,
            'Terminé' => self::COMPLETED,
            'Annulé' => self::CANCELLED,
        ];
    }
}
