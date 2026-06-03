<?php

namespace App\Tests\Entity;

use App\Entity\Vehicle;
use App\Entity\VehicleBadge;
use App\Enum\VehicleBadgeCategory;
use PHPUnit\Framework\TestCase;

class VehicleBadgeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $badge = new VehicleBadge();

        // vérifie les valeurs par défaut
        $this->assertNull($badge->getId());
        $this->assertNull($badge->getColor());
        $this->assertNull($badge->getIcon());
        $this->assertSame(0, $badge->getPriority());
        $this->assertTrue($badge->isActive());

        // la collection doit être initialisée
        $this->assertCount(0, $badge->getVehicles());
    }

    public function testCodeGetterAndSetter(): void
    {
        $badge = new VehicleBadge();

        // vérifie trim et conversion en majuscules
        $badge->setCode('  promo_ete  ');

        $this->assertSame(
            'PROMO_ETE',
            $badge->getCode()
        );
    }

    public function testLabelGetterAndSetter(): void
    {
        $badge = new VehicleBadge();

        // vérifie le trim automatique
        $badge->setLabel('  promotion été  ');

        $this->assertSame(
            'promotion été',
            $badge->getLabel()
        );
    }

    public function testCategoryGetterAndSetter(): void
    {
        $badge = new VehicleBadge();

        $category = VehicleBadgeCategory::cases()[0];

        $badge->setCategory($category);

        $this->assertSame(
            $category,
            $badge->getCategory()
        );
    }

    public function testPriorityGetterAndSetter(): void
    {
        $badge = new VehicleBadge();

        $badge->setPriority(50);

        $this->assertSame(
            50,
            $badge->getPriority()
        );
    }

    public function testPriorityCannotBeNegative(): void
    {
        $badge = new VehicleBadge();

        // la priorité doit être forcée à 0
        $badge->setPriority(-10);

        $this->assertSame(
            0,
            $badge->getPriority()
        );
    }

    public function testColorGetterAndSetter(): void
    {
        $badge = new VehicleBadge();

        // vérifie trim et conversion en minuscules
        $badge->setColor('  #FFAA00  ');

        $this->assertSame(
            '#ffaa00',
            $badge->getColor()
        );
    }

    public function testColorCanBeNull(): void
    {
        $badge = new VehicleBadge();

        $badge->setColor(null);

        $this->assertNull(
            $badge->getColor()
        );
    }

    public function testIconGetterAndSetter(): void
    {
        $badge = new VehicleBadge();

        // vérifie le trim automatique
        $badge->setIcon('  fa-solid fa-star  ');

        $this->assertSame(
            'fa-solid fa-star',
            $badge->getIcon()
        );
    }

    public function testIconCanBeNull(): void
    {
        $badge = new VehicleBadge();

        $badge->setIcon(null);

        $this->assertNull(
            $badge->getIcon()
        );
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $badge = new VehicleBadge();

        $badge->setIsActive(false);

        $this->assertFalse(
            $badge->isActive()
        );

        $badge->setIsActive(true);

        $this->assertTrue(
            $badge->isActive()
        );
    }

    public function testAddVehicle(): void
    {
        $badge = new VehicleBadge();

        $vehicle = $this->createMock(Vehicle::class);

        $badge->addVehicle($vehicle);

        $this->assertCount(
            1,
            $badge->getVehicles()
        );

        $this->assertTrue(
            $badge->getVehicles()->contains($vehicle)
        );
    }

    public function testAddVehicleWhenAlreadyAssigned(): void
    {
        $badge = new VehicleBadge();

        $vehicle = $this->createMock(Vehicle::class);

        $badge->addVehicle($vehicle);
        $badge->addVehicle($vehicle);

        // le véhicule ne doit être présent qu'une seule fois
        $this->assertCount(
            1,
            $badge->getVehicles()
        );
    }

    public function testRemoveVehicle(): void
    {
        $badge = new VehicleBadge();

        $vehicle = $this->createMock(Vehicle::class);

        $badge->addVehicle($vehicle);

        $this->assertCount(
            1,
            $badge->getVehicles()
        );

        $badge->removeVehicle($vehicle);

        $this->assertCount(
            0,
            $badge->getVehicles()
        );
    }

    public function testRemoveVehicleNotPresent(): void
    {
        $badge = new VehicleBadge();

        $vehicle = $this->createMock(Vehicle::class);

        $badge->removeVehicle($vehicle);

        $this->assertCount(
            0,
            $badge->getVehicles()
        );
    }
}
