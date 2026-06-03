<?php

namespace App\Tests\Entity;

use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Enum\SupplierType;
use PHPUnit\Framework\TestCase;

class SupplierTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $supplier = new Supplier();

        // vérifie les valeurs par défaut
        $this->assertNull($supplier->getId());
        $this->assertNull($supplier->getName());
        $this->assertNull($supplier->getAddress());
        $this->assertNull($supplier->getCity());
        $this->assertNull($supplier->getPostalCode());
        $this->assertSame('France', $supplier->getCountry());
        $this->assertNull($supplier->getSiret());
        $this->assertNull($supplier->getEmail());
        $this->assertNull($supplier->getPhone());
        $this->assertNull($supplier->getType());
        $this->assertNull($supplier->getAverageDeliveryDelay());
        $this->assertNull($supplier->getRating());

        // la collection doit être initialisée
        $this->assertCount(0, $supplier->getVehicles());
    }

    public function testNameGetterAndSetter(): void
    {
        $supplier = new Supplier();

        // vérifie le trim automatique
        $supplier->setName('  fournisseur test  ');

        $this->assertSame('fournisseur test', $supplier->getName());
    }

    public function testAddressGetterAndSetter(): void
    {
        $supplier = new Supplier();

        // vérifie le trim automatique
        $supplier->setAddress('  10 rue de paris  ');

        $this->assertSame('10 rue de paris', $supplier->getAddress());

        // vérifie la gestion du null
        $supplier->setAddress(null);

        $this->assertNull($supplier->getAddress());
    }

    public function testCityGetterAndSetter(): void
    {
        $supplier = new Supplier();

        // vérifie le trim automatique
        $supplier->setCity('  lyon  ');

        $this->assertSame('lyon', $supplier->getCity());

        // vérifie la gestion du null
        $supplier->setCity(null);

        $this->assertNull($supplier->getCity());
    }

    public function testPostalCodeGetterAndSetter(): void
    {
        $supplier = new Supplier();

        $supplier->setPostalCode('75000');

        $this->assertSame('75000', $supplier->getPostalCode());

        $supplier->setPostalCode(null);

        $this->assertNull($supplier->getPostalCode());
    }

    public function testCountryGetterAndSetter(): void
    {
        $supplier = new Supplier();

        // vérifie le trim automatique
        $supplier->setCountry('  belgique  ');

        $this->assertSame('belgique', $supplier->getCountry());

        // vérifie la gestion du null
        $supplier->setCountry(null);

        $this->assertNull($supplier->getCountry());
    }

    public function testSiretGetterAndSetter(): void
    {
        $supplier = new Supplier();

        // vérifie le trim et strtoupper
        $supplier->setSiret(' 12345678901234 ');

        $this->assertSame('12345678901234', $supplier->getSiret());

        $supplier->setSiret(null);

        $this->assertNull($supplier->getSiret());
    }

    public function testEmailGetterAndSetter(): void
    {
        $supplier = new Supplier();

        // vérifie trim et conversion en minuscules
        $supplier->setEmail('  TEST@EXEMPLE.COM  ');

        $this->assertSame('test@exemple.com', $supplier->getEmail());

        $supplier->setEmail(null);

        $this->assertNull($supplier->getEmail());
    }

    public function testPhoneGetterAndSetter(): void
    {
        $supplier = new Supplier();

        // vérifie le trim automatique
        $supplier->setPhone(' 01 02 03 04 05 ');

        $this->assertSame('01 02 03 04 05', $supplier->getPhone());

        $supplier->setPhone(null);

        $this->assertNull($supplier->getPhone());
    }

    public function testTypeGetterAndSetter(): void
    {
        $supplier = new Supplier();

        $supplier->setType(SupplierType::MANUFACTURER);

        $this->assertSame(
            SupplierType::MANUFACTURER,
            $supplier->getType()
        );
    }

    public function testAverageDeliveryDelayGetterAndSetter(): void
    {
        $supplier = new Supplier();

        $supplier->setAverageDeliveryDelay(15);

        $this->assertSame(
            15,
            $supplier->getAverageDeliveryDelay()
        );

        $supplier->setAverageDeliveryDelay(null);

        $this->assertNull(
            $supplier->getAverageDeliveryDelay()
        );
    }

    public function testRatingGetterAndSetter(): void
    {
        $supplier = new Supplier();

        $supplier->setRating('4.5');

        $this->assertSame(
            '4.5',
            $supplier->getRating()
        );

        $supplier->setRating(null);

        $this->assertNull(
            $supplier->getRating()
        );
    }

    public function testAddVehicle(): void
    {
        $supplier = new Supplier();
        $vehicle = $this->createMock(Vehicle::class);

        // vérifie que le fournisseur est injecté dans le véhicule
        $vehicle
            ->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addVehicle($vehicle);

        $this->assertCount(
            1,
            $supplier->getVehicles()
        );

        $this->assertTrue(
            $supplier->getVehicles()->contains($vehicle)
        );
    }

    public function testAddVehicleWhenAlreadyAssigned(): void
    {
        $supplier = new Supplier();
        $vehicle = $this->createMock(Vehicle::class);

        // le setter ne doit être appelé qu'une seule fois
        $vehicle
            ->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addVehicle($vehicle);
        $supplier->addVehicle($vehicle);

        $this->assertCount(
            1,
            $supplier->getVehicles()
        );
    }

    public function testRemoveVehicle(): void
    {
        $supplier = new Supplier();

        $vehicle = $this->createMock(Vehicle::class);

        $vehicle
            ->method('getSupplier')
            ->willReturn($supplier);

        $vehicle
            ->expects($this->exactly(2))
            ->method('setSupplier');

        $supplier->addVehicle($vehicle);
        $supplier->removeVehicle($vehicle);

        $this->assertCount(
            0,
            $supplier->getVehicles()
        );
    }

    public function testRemoveVehicleWhenSupplierIsDifferent(): void
    {
        $supplier = new Supplier();

        $vehicle = $this->createMock(Vehicle::class);

        $vehicle
            ->method('getSupplier')
            ->willReturn(null);

        // uniquement lors du addVehicle
        $vehicle
            ->expects($this->once())
            ->method('setSupplier')
            ->with($supplier);

        $supplier->addVehicle($vehicle);
        $supplier->removeVehicle($vehicle);

        $this->assertCount(
            0,
            $supplier->getVehicles()
        );
    }

    public function testRemoveVehicleNotPresent(): void
    {
        $supplier = new Supplier();

        $vehicle = $this->createMock(Vehicle::class);

        $supplier->removeVehicle($vehicle);

        $this->assertCount(
            0,
            $supplier->getVehicles()
        );
    }
}
