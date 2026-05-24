<?php

namespace App\Enum;

enum FinancingType: string
{
    case CASH = 'Cash';
    case CREDIT = 'Crédit';
    case LOA = 'LOA';
    case LLD = 'LLD';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Comptant',
            self::CREDIT => 'Crédit',
            self::LOA => 'LOA',
            self::LLD => 'LLD',
        };
    }

    /**
     * icône fontawesome associée au type de financement
     */
    public function icon(): string
    {
        return match ($this) {
            self::CASH => 'fa-solid fa-sack-dollar',
            self::CREDIT => 'fa-solid fa-landmark',
            self::LOA => 'fa-solid fa-car-side',
            self::LLD => 'fa-solid fa-calendar-days',
        };
    }

    /**
     * choix pour formulaire symfony
     */
    public static function choices(): array
    {
        return [
            'Comptant' => self::CASH,
            'Crédit' => self::CREDIT,
            'LOA' => self::LOA,
            'LLD' => self::LLD,
        ];
    }
}
