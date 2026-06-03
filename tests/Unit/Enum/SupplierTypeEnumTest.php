<?php

namespace App\Tests\Unit\Enum;

use App\Enum\SupplierType;
use PHPUnit\Framework\TestCase;

class SupplierTypeEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie les libellés de tous les types de fournisseurs

        $this->assertSame(
            'Fabricant',
            SupplierType::MANUFACTURER->label()
        );

        $this->assertSame(
            'Importateur',
            SupplierType::IMPORTER->label()
        );

        $this->assertSame(
            'Grossiste',
            SupplierType::WHOLESALER->label()
        );

        $this->assertSame(
            'Groupe de concessions',
            SupplierType::DEALER_GROUP->label()
        );

        $this->assertSame(
            'Société de leasing',
            SupplierType::LEASING_COMPANY->label()
        );

        $this->assertSame(
            'Courtier automobile',
            SupplierType::BROKER->label()
        );

        $this->assertSame(
            'Vente aux enchères',
            SupplierType::AUCTION->label()
        );

        $this->assertSame(
            'Autre',
            SupplierType::OTHER->label()
        );
    }

    public function testChoices(): void
    {
        // vérifie la structure utilisée par les ChoiceType Symfony

        $this->assertSame(
            [
                'Fabricant' => SupplierType::MANUFACTURER,
                'Importateur' => SupplierType::IMPORTER,
                'Grossiste' => SupplierType::WHOLESALER,
                'Groupe de concessions' => SupplierType::DEALER_GROUP,
                'Société de leasing' => SupplierType::LEASING_COMPANY,
                'Courtier automobile' => SupplierType::BROKER,
                'Vente aux enchères' => SupplierType::AUCTION,
                'Autre' => SupplierType::OTHER,
            ],
            SupplierType::choices()
        );
    }

    public function testAllCasesHaveLabel(): void
    {
        // vérifie que chaque valeur possède un libellé non vide

        foreach (SupplierType::cases() as $type) {
            $this->assertNotSame(
                '',
                $type->label()
            );
        }
    }

    public function testChoicesContainsAllCases(): void
    {
        // vérifie qu'aucune valeur de l'enum n'est oubliée

        $choices = SupplierType::choices();

        $this->assertCount(
            count(SupplierType::cases()),
            $choices
        );

        foreach (SupplierType::cases() as $type) {
            $this->assertContains(
                $type,
                $choices
            );
        }
    }

    public function testEnumValues(): void
    {
        // vérifie les valeurs stockées en base

        $this->assertSame(
            'manufacturer',
            SupplierType::MANUFACTURER->value
        );

        $this->assertSame(
            'importer',
            SupplierType::IMPORTER->value
        );

        $this->assertSame(
            'wholesaler',
            SupplierType::WHOLESALER->value
        );

        $this->assertSame(
            'dealer_group',
            SupplierType::DEALER_GROUP->value
        );

        $this->assertSame(
            'leasing_company',
            SupplierType::LEASING_COMPANY->value
        );

        $this->assertSame(
            'broker',
            SupplierType::BROKER->value
        );

        $this->assertSame(
            'auction',
            SupplierType::AUCTION->value
        );

        $this->assertSame(
            'other',
            SupplierType::OTHER->value
        );
    }
}
