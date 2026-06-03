<?php

namespace App\Enum;

enum VehicleBadgeCategory: string
{
    case STATE = 'state';
    case COMMERCIAL = 'commercial';
    case ECOLOGY = 'ecology';
    case TRUST = 'trust';
    case AUDIENCE = 'audience';

    public function label(): string
    {
        return match ($this) {
            self::STATE => 'État',
            self::COMMERCIAL => 'Commercial',
            self::ECOLOGY => 'Écologie',
            self::TRUST => 'Confiance',
            self::AUDIENCE => 'Audience',
        };
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case;
        }

        return $choices;
    }
}
