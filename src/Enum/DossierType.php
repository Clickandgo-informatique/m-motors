<?php

namespace App\Enum;

enum DossierType: string
{
    case PURCHASE = 'purchase';
    case FINANCING = 'financing';

    /**
     * Label lisible (affichage Twig)
     */
    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Achat',
            self::FINANCING => 'Financement',
        };
    }

    /**
     * Helper métier
     */
    public function isFinancing(): bool
    {
        return $this === self::FINANCING;
    }

    /**
     * Pour les formulaires Symfony
     */
    public static function choices(): array
    {
        return [
            'Achat' => self::PURCHASE,
            'Financement' => self::FINANCING,
        ];
    }
}
