<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
use App\Enum\VehicleUsageType;
use PHPUnit\Framework\TestCase;

class VehicleStatusTest extends TestCase
{
    // vérifie le changement manuel de statut
    public function testSetStatus(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::RESERVED
        );

        self::assertSame(
            VehicleStatus::RESERVED,
            $vehicle->getStatus()
        );
    }

    // vérifie le changement du type d'usage
    public function testSetUsageType(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setUsageType(
            VehicleUsageType::SALE
        );

        self::assertSame(
            VehicleUsageType::SALE,
            $vehicle->getUsageType()
        );
    }

    // vérifie la réservation
    public function testReserve(): void
    {
        $vehicle = new Vehicle();

        $vehicle->reserve();

        self::assertSame(
            VehicleStatus::RESERVED,
            $vehicle->getStatus()
        );
    }

    // vérifie le passage en vendu
    public function testMarkAsSold(): void
    {
        $vehicle = new Vehicle();

        $vehicle->markAsSold();

        self::assertSame(
            VehicleStatus::SOLD,
            $vehicle->getStatus()
        );
    }

    // vérifie le passage en location
    public function testMarkAsRented(): void
    {
        $vehicle = new Vehicle();

        $vehicle->markAsRented();

        self::assertSame(
            VehicleStatus::RENTED,
            $vehicle->getStatus()
        );
    }

    // vérifie le retour à la disponibilité
    public function testMakeAvailable(): void
    {
        $vehicle = new Vehicle();

        $vehicle->markAsSold();

        self::assertSame(
            VehicleStatus::SOLD,
            $vehicle->getStatus()
        );

        $vehicle->makeAvailable();

        self::assertSame(
            VehicleStatus::AVAILABLE_FOR_SALE,
            $vehicle->getStatus()
        );
    }

    // vérifie qu'un véhicule réservé est verrouillé
    public function testReservedVehicleIsLocked(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::RESERVED
        );

        self::assertTrue(
            $vehicle->isLocked()
        );
    }

    // vérifie qu'un véhicule loué est verrouillé
    public function testRentedVehicleIsLocked(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::RENTED
        );

        self::assertTrue(
            $vehicle->isLocked()
        );
    }

    // vérifie qu'un véhicule vendu est verrouillé
    public function testSoldVehicleIsLocked(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::SOLD
        );

        self::assertTrue(
            $vehicle->isLocked()
        );
    }

    // vérifie qu'un véhicule en maintenance est verrouillé
    public function testMaintenanceVehicleIsLocked(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::MAINTENANCE
        );

        self::assertTrue(
            $vehicle->isLocked()
        );
    }

    // vérifie qu'un véhicule disponible n'est pas verrouillé
    public function testAvailableVehicleIsNotLocked(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::AVAILABLE_FOR_SALE
        );

        self::assertFalse(
            $vehicle->isLocked()
        );
    }

    // vérifie qu'un véhicule disponible est considéré disponible
    public function testAvailableVehicleIsAvailable(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::AVAILABLE_FOR_SALE
        );

        self::assertTrue(
            $vehicle->isAvailable()
        );
    }

    // vérifie qu'un véhicule réservé n'est plus disponible
    public function testReservedVehicleIsNotAvailable(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setStatus(
            VehicleStatus::RESERVED
        );

        self::assertFalse(
            $vehicle->isAvailable()
        );
    }

    // vérifie le chaînage des setters métier
    public function testFluentSetters(): void
    {
        $vehicle = new Vehicle();

        self::assertSame(
            $vehicle,
            $vehicle->setStatus(
                VehicleStatus::RESERVED
            )
        );

        self::assertSame(
            $vehicle,
            $vehicle->setUsageType(
                VehicleUsageType::SALE
            )
        );
    }
}
