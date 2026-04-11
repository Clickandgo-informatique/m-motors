<?php

namespace App\Enum;

enum DossierStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Label lisible (affichage)
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumis',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Refusé',
        };
    }

    /**
     * Vérifie si une transition est autorisée
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => $target === self::SUBMITTED,

            self::SUBMITTED => in_array($target, [
                self::APPROVED,
                self::REJECTED
            ], true),

            self::APPROVED => false,
            self::REJECTED => false,
        };
    }

    /**
     * Helpers métier
     */
    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Liste pour formulaires (optionnel)
     */
    public static function choices(): array
    {
        return [
            'Brouillon' => self::DRAFT,
            'Soumis' => self::SUBMITTED,
            'Approuvé' => self::APPROVED,
            'Refusé' => self::REJECTED,
        ];
    }
}
