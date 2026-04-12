<?php

namespace App\Enum;

enum DossierStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
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
            self::UNDER_REVIEW => 'En étude',
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

            // Brouillon → Soumis uniquement
            self::DRAFT => $target === self::SUBMITTED,

            // Soumis → En étude ou refus direct
            self::SUBMITTED => in_array($target, [
                self::UNDER_REVIEW,
                self::REJECTED
            ], true),

            // En étude → Accepté ou refusé
            self::UNDER_REVIEW => in_array($target, [
                self::APPROVED,
                self::REJECTED
            ], true),

            // États finaux
            self::APPROVED => false,
            self::REJECTED => false,
        };
    }

    // ========================= HELPERS =========================

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function isUnderReview(): bool
    {
        return $this === self::UNDER_REVIEW;
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
     * États finaux (important pour UI / logique)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::REJECTED
        ], true);
    }

    /**
     * Liste pour formulaires
     */
    public static function choices(): array
    {
        return [
            'Brouillon' => self::DRAFT,
            'Soumis' => self::SUBMITTED,
            'En étude' => self::UNDER_REVIEW,
            'Approuvé' => self::APPROVED,
            'Refusé' => self::REJECTED,
        ];
    }

    /**
     * Badge CSS (utile Twig)
     */
    public function badge(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'info',
            self::UNDER_REVIEW => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
