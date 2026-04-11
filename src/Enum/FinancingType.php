<?php

namespace App\Enum;

enum FinancingType: string
{
    case CASH = 'cash';
    case CREDIT = 'credit';
    case LOA = 'loa';
    case LLD = 'lld';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Comptant',
            self::CREDIT => 'Crédit',
            self::LOA => 'LOA',
            self::LLD => 'LLD',
        };
    }

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
