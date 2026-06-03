<?php

namespace App\Tests\Unit\Enum;

use App\Enum\DossierType;
use App\Enum\VehicleUsageType;
use PHPUnit\Framework\TestCase;

class VehicleUsageTypeEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie les libellés affichés en interface

        $this->assertSame(
            'Vente',
            VehicleUsageType::SALE->label()
        );

        $this->assertSame(
            'Location',
            VehicleUsageType::RENT->label()
        );

        $this->assertSame(
            'Vente & Location',
            VehicleUsageType::BOTH->label()
        );
    }

    public function testAllowedDossierTypesForSale(): void
    {
        // un véhicule vente n'autorise qu'un dossier achat

        $this->assertSame(
            [DossierType::PURCHASE],
            VehicleUsageType::SALE->allowedDossierTypes()
        );
    }

    public function testAllowedDossierTypesForRent(): void
    {
        // un véhicule location n'autorise qu'un dossier location

        $this->assertSame(
            [DossierType::RENTAL],
            VehicleUsageType::RENT->allowedDossierTypes()
        );
    }

    public function testAllowedDossierTypesForBoth(): void
    {
        // un véhicule mixte autorise achat et location

        $this->assertSame(
            [
                DossierType::PURCHASE,
                DossierType::RENTAL,
            ],
            VehicleUsageType::BOTH->allowedDossierTypes()
        );
    }

    public function testAllCasesHaveLabel(): void
    {
        // vérifie que chaque valeur possède un libellé non vide

        foreach (VehicleUsageType::cases() as $usageType) {
            $this->assertNotSame(
                '',
                $usageType->label()
            );
        }
    }

    public function testAllCasesHaveAllowedDossierTypes(): void
    {
        // vérifie que chaque valeur retourne au moins un type de dossier

        foreach (VehicleUsageType::cases() as $usageType) {
            $this->assertNotEmpty(
                $usageType->allowedDossierTypes()
            );
        }
    }

    public function testEnumValues(): void
    {
        // vérifie les valeurs stockées en base

        $this->assertSame(
            'sale',
            VehicleUsageType::SALE->value
        );

        $this->assertSame(
            'rent',
            VehicleUsageType::RENT->value
        );

        $this->assertSame(
            'both',
            VehicleUsageType::BOTH->value
        );
    }
}
