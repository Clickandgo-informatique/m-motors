<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
use PHPUnit\Framework\TestCase;

class VehicleBasicTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $vehicle = new Vehicle();

        self::assertNull($vehicle->getId());
        self::assertNull($vehicle->getVin());
        self::assertNull($vehicle->getRegistrationNumber());
        self::assertNull($vehicle->getMileage());
        self::assertNull($vehicle->getPrice());
        self::assertNull($vehicle->getFirstRegistrationDate());

        self::assertSame(
            VehicleStatus::AVAILABLE_FOR_SALE,
            $vehicle->getStatus()
        );

        self::assertFalse(
            $vehicle->isFeatured()
        );

        self::assertCount(0, $vehicle->getImages());
        self::assertCount(0, $vehicle->getDossiers());
        self::assertCount(0, $vehicle->getMaintenances());
        self::assertCount(0, $vehicle->getRentals());
        self::assertCount(0, $vehicle->getSales());
        self::assertCount(0, $vehicle->getFavorites());
        self::assertCount(0, $vehicle->getFeatures());
        self::assertCount(0, $vehicle->getBadges());
    }

    // vérifie la normalisation du vin
    public function testVinNormalization(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setVin('  vf1abc12345678901  ');

        self::assertSame(
            'VF1ABC12345678901',
            $vehicle->getVin()
        );
    }

    // vérifie la normalisation de l'immatriculation
    public function testRegistrationNumberNormalization(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setRegistrationNumber(' ab-123-cd ');

        self::assertSame(
            'AB-123-CD',
            $vehicle->getRegistrationNumber()
        );
    }

    // vérifie qu'une immatriculation peut être null
    public function testRegistrationNumberCanBeNull(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setRegistrationNumber(null);

        self::assertNull(
            $vehicle->getRegistrationNumber()
        );
    }

    // vérifie le kilométrage
    public function testMileage(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setMileage(125000);

        self::assertSame(
            125000,
            $vehicle->getMileage()
        );
    }

    // vérifie le prix
    public function testPrice(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setPrice(24990);

        self::assertSame(
            24990,
            $vehicle->getPrice()
        );
    }

    // vérifie la date de première mise en circulation
    public function testFirstRegistrationDate(): void
    {
        $vehicle = new Vehicle();

        $date = new \DateTimeImmutable('2024-01-15');

        $vehicle->setFirstRegistrationDate($date);

        self::assertSame(
            $date,
            $vehicle->getFirstRegistrationDate()
        );
    }

    // vérifie le flag véhicule mis en avant
    public function testFeaturedFlag(): void
    {
        $vehicle = new Vehicle();

        self::assertFalse(
            $vehicle->isFeatured()
        );

        $vehicle->setIsFeatured(true);

        self::assertTrue(
            $vehicle->isFeatured()
        );

        $vehicle->setIsFeatured(false);

        self::assertFalse(
            $vehicle->isFeatured()
        );
    }

    // vérifie le chaînage des setters simples
    public function testFluentSetters(): void
    {
        $vehicle = new Vehicle();

        self::assertSame(
            $vehicle,
            $vehicle->setVin('VF1ABC12345678901')
        );

        self::assertSame(
            $vehicle,
            $vehicle->setRegistrationNumber('AA-123-AA')
        );

        self::assertSame(
            $vehicle,
            $vehicle->setMileage(100000)
        );

        self::assertSame(
            $vehicle,
            $vehicle->setPrice(20000)
        );

        self::assertSame(
            $vehicle,
            $vehicle->setFirstRegistrationDate(
                new \DateTimeImmutable()
            )
        );

        self::assertSame(
            $vehicle,
            $vehicle->setIsFeatured(true)
        );
    }
}
