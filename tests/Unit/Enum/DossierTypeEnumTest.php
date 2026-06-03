<?php

namespace App\Tests\Unit\Enum;

use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\VehicleUsageType;
use PHPUnit\Framework\TestCase;

class DossierTypeEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie les libellés affichés en interface

        $this->assertSame(
            'achat',
            DossierType::PURCHASE->label()
        );

        $this->assertSame(
            'location',
            DossierType::RENTAL->label()
        );
    }

    public function testChoices(): void
    {
        // vérifie les choix utilisés dans les formulaires

        $this->assertSame(
            [
                'achat' => DossierType::PURCHASE,
                'location' => DossierType::RENTAL,
            ],
            DossierType::choices()
        );
    }

    public function testApplyVehicleOnSubmit(): void
    {
        // la création d'un dossier doit réserver le véhicule

        $vehicle = $this->createMock(Vehicle::class);

        $vehicle
            ->expects($this->once())
            ->method('reserve');

        DossierType::PURCHASE->applyVehicleOnSubmit($vehicle);
    }

    public function testApplyVehicleValidationForPurchase(): void
    {
        // un dossier achat doit vendre le véhicule

        $vehicle = $this->createMock(Vehicle::class);

        $vehicle
            ->expects($this->once())
            ->method('markAsSold');

        DossierType::PURCHASE->applyVehicleValidation($vehicle);
    }

    public function testApplyVehicleValidationForRental(): void
    {
        // un dossier location doit louer le véhicule

        $vehicle = $this->createMock(Vehicle::class);

        $vehicle
            ->expects($this->once())
            ->method('markAsRented');

        DossierType::RENTAL->applyVehicleValidation($vehicle);
    }

    public function testApplyVehicleRejection(): void
    {
        // un refus remet le véhicule disponible

        $vehicle = $this->createMock(Vehicle::class);

        $vehicle
            ->expects($this->once())
            ->method('makeAvailable');

        DossierType::PURCHASE->applyVehicleRejection($vehicle);
    }

    public function testIsPurchase(): void
    {
        // vérifie la détection du type achat

        $this->assertTrue(
            DossierType::PURCHASE->isPurchase()
        );

        $this->assertFalse(
            DossierType::RENTAL->isPurchase()
        );
    }

    public function testIsRental(): void
    {
        // vérifie la détection du type location

        $this->assertTrue(
            DossierType::RENTAL->isRental()
        );

        $this->assertFalse(
            DossierType::PURCHASE->isRental()
        );
    }

    public function testFromVehicleUsageTypeSale(): void
    {
        // un véhicule vente n'autorise que l'achat

        $this->assertSame(
            [DossierType::PURCHASE],
            DossierType::fromVehicleUsageType(
                VehicleUsageType::SALE
            )
        );
    }

    public function testFromVehicleUsageTypeRent(): void
    {
        // un véhicule location n'autorise que la location

        $this->assertSame(
            [DossierType::RENTAL],
            DossierType::fromVehicleUsageType(
                VehicleUsageType::RENT
            )
        );
    }

    public function testFromVehicleUsageTypeBoth(): void
    {
        // un véhicule mixte autorise les deux types

        $this->assertSame(
            [
                DossierType::PURCHASE,
                DossierType::RENTAL,
            ],
            DossierType::fromVehicleUsageType(
                VehicleUsageType::BOTH
            )
        );
    }

    public function testPurchaseAllowedForSaleVehicle(): void
    {
        // un véhicule vente accepte un dossier achat

        $this->assertTrue(
            DossierType::isAllowedForVehicleUsage(
                DossierType::PURCHASE,
                VehicleUsageType::SALE
            )
        );
    }

    public function testRentalNotAllowedForSaleVehicle(): void
    {
        // un véhicule vente refuse un dossier location

        $this->assertFalse(
            DossierType::isAllowedForVehicleUsage(
                DossierType::RENTAL,
                VehicleUsageType::SALE
            )
        );
    }

    public function testRentalAllowedForRentVehicle(): void
    {
        // un véhicule location accepte un dossier location

        $this->assertTrue(
            DossierType::isAllowedForVehicleUsage(
                DossierType::RENTAL,
                VehicleUsageType::RENT
            )
        );
    }

    public function testPurchaseNotAllowedForRentVehicle(): void
    {
        // un véhicule location refuse un dossier achat

        $this->assertFalse(
            DossierType::isAllowedForVehicleUsage(
                DossierType::PURCHASE,
                VehicleUsageType::RENT
            )
        );
    }

    public function testBothTypesAllowedForMixedVehicle(): void
    {
        // un véhicule mixte accepte les deux types de dossier

        $this->assertTrue(
            DossierType::isAllowedForVehicleUsage(
                DossierType::PURCHASE,
                VehicleUsageType::BOTH
            )
        );

        $this->assertTrue(
            DossierType::isAllowedForVehicleUsage(
                DossierType::RENTAL,
                VehicleUsageType::BOTH
            )
        );
    }
}
