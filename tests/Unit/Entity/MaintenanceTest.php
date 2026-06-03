<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Maintenance;
use App\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

class MaintenanceTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $maintenance = new Maintenance();

        self::assertNull($maintenance->getId());
        self::assertNull($maintenance->getDate());
        self::assertNull($maintenance->getDescription());
        self::assertNull($maintenance->getMileage());
        self::assertNull($maintenance->getVehicle());
    }

    // vérifie la date
    public function testDate(): void
    {
        $maintenance = new Maintenance();

        $date = new \DateTime('2026-01-15');

        $maintenance->setDate($date);

        self::assertSame(
            $date,
            $maintenance->getDate()
        );
    }

    // vérifie la description
    public function testDescription(): void
    {
        $maintenance = new Maintenance();

        $maintenance->setDescription('Révision annuelle');

        self::assertSame(
            'Révision annuelle',
            $maintenance->getDescription()
        );
    }

    // vérifie le trim automatique de la description
    public function testDescriptionIsTrimmed(): void
    {
        $maintenance = new Maintenance();

        $maintenance->setDescription(
            '   Révision complète   '
        );

        self::assertSame(
            'Révision complète',
            $maintenance->getDescription()
        );
    }

    // vérifie le kilométrage
    public function testMileage(): void
    {
        $maintenance = new Maintenance();

        $maintenance->setMileage(125000);

        self::assertSame(
            125000,
            $maintenance->getMileage()
        );
    }

    // vérifie qu'un kilométrage peut être null
    public function testMileageCanBeNull(): void
    {
        $maintenance = new Maintenance();

        $maintenance->setMileage(null);

        self::assertNull(
            $maintenance->getMileage()
        );
    }

    // vérifie l'association véhicule
    public function testVehicle(): void
    {
        $maintenance = new Maintenance();
        $vehicle = new Vehicle();

        $maintenance->setVehicle($vehicle);

        self::assertSame(
            $vehicle,
            $maintenance->getVehicle()
        );
    }

    // vérifie qu'un véhicule peut être null
    public function testVehicleCanBeNull(): void
    {
        $maintenance = new Maintenance();

        $maintenance->setVehicle(null);

        self::assertNull(
            $maintenance->getVehicle()
        );
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $maintenance = new Maintenance();

        self::assertSame(
            $maintenance,
            $maintenance->setDate(new \DateTime())
        );

        self::assertSame(
            $maintenance,
            $maintenance->setDescription('Révision')
        );

        self::assertSame(
            $maintenance,
            $maintenance->setMileage(100000)
        );

        self::assertSame(
            $maintenance,
            $maintenance->setVehicle(new Vehicle())
        );
    }

    // vérifie plusieurs mises à jour successives
    public function testMultipleUpdates(): void
    {
        $maintenance = new Maintenance();

        $maintenance->setDescription('Vidange');
        $maintenance->setDescription('Révision');

        self::assertSame(
            'Révision',
            $maintenance->getDescription()
        );

        $maintenance->setMileage(100000);
        $maintenance->setMileage(120000);

        self::assertSame(
            120000,
            $maintenance->getMileage()
        );
    }
}
