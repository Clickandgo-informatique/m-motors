<?php

namespace App\Enum;

enum SupplierType: string
{
    case MANUFACTURER = 'manufacturer';
    case IMPORTER = 'importer';
    case WHOLESALER = 'wholesaler';
    case DEALER_GROUP = 'dealer_group';
    case LEASING_COMPANY = 'leasing_company';
    case BROKER = 'broker';
    case AUCTION = 'auction';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::MANUFACTURER   => 'Fabricant',
            self::IMPORTER       => 'Importateur',
            self::WHOLESALER     => 'Grossiste',
            self::DEALER_GROUP   => 'Groupe de concessions',
            self::LEASING_COMPANY=> 'Société de leasing',
            self::BROKER         => 'Courtier automobile',
            self::AUCTION        => 'Vente aux enchères',
            self::OTHER          => 'Autre',
        };
    }

    /**
     * Utile pour Symfony ChoiceType
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case;
        }

        return $choices;
    }
}