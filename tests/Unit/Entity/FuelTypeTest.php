<?php

namespace App\Tests\Unit\Entity;

use App\Entity\FuelType;
use App\Entity\Vehicle;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FuelTypeTest extends KernelTestCase
{
    // on initialise le validator symfony
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    // vérifie que l'id est null par défaut
    public function testIdIsNullByDefault(): void
    {
        $fuelType = new FuelType();

        $this->assertNull($fuelType->getId());
    }

    // vérifie la création de l'entité
    public function testFuelTypeCreation(): void
    {
        $fuelType = new FuelType();

        $this->assertInstanceOf(FuelType::class, $fuelType);
    }

    // vérifie que le nom est null par défaut
    public function testNameIsNullByDefault(): void
    {
        $fuelType = new FuelType();

        $this->assertNull($fuelType->getName());
    }

    // vérifie le setter de name
    public function testSetName(): void
    {
        $fuelType = new FuelType();

        $fuelType->setName('diesel');

        $this->assertSame('diesel', $fuelType->getName());
    }

    // vérifie que les espaces sont supprimés automatiquement
    public function testNameIsTrimmed(): void
    {
        $fuelType = new FuelType();

        $fuelType->setName('  essence  ');

        $this->assertSame('essence', $fuelType->getName());
    }

    // vérifie que le name ne peut pas être vide
    public function testNameCannotBeBlank(): void
    {
        $fuelType = new FuelType();

        $fuelType->setName(' ');

        $errors = $this->validator->validate($fuelType);

        $this->assertGreaterThanOrEqual(1, count($errors));
    }

    // vérifie que le name est trop court
    public function testNameIsTooShort(): void
    {
        $fuelType = new FuelType();

        $fuelType->setName('a');

        $errors = $this->validator->validate($fuelType);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que le name est trop long
    public function testNameIsTooLong(): void
    {
        $fuelType = new FuelType();

        $fuelType->setName(str_repeat('a', 51));

        $errors = $this->validator->validate($fuelType);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que les caractères invalides sont rejetés
    public function testNameRejectsInvalidCharacters(): void
    {
        $fuelType = new FuelType();

        $fuelType->setName('@@@###');

        $errors = $this->validator->validate($fuelType);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que la collection vehicles est vide par défaut
    public function testVehiclesCollectionIsEmptyByDefault(): void
    {
        $fuelType = new FuelType();

        $this->assertInstanceOf(Collection::class, $fuelType->getVehicles());
        $this->assertCount(0, $fuelType->getVehicles());
    }

    // vérifie l'ajout d'un véhicule
    public function testAddVehicle(): void
    {
        $fuelType = new FuelType();
        $vehicle = new Vehicle();

        $fuelType->addVehicle($vehicle);

        $this->assertTrue($fuelType->getVehicles()->contains($vehicle));
        $this->assertCount(1, $fuelType->getVehicles());
        $this->assertSame($fuelType, $vehicle->getFuelType());
    }

    // vérifie la suppression d'un véhicule
    public function testRemoveVehicle(): void
    {
        $fuelType = new FuelType();
        $vehicle = new Vehicle();

        $fuelType->addVehicle($vehicle);
        $fuelType->removeVehicle($vehicle);

        $this->assertCount(0, $fuelType->getVehicles());
        $this->assertNull($vehicle->getFuelType());
    }

    // vérifie qu'un véhicule ne peut pas être ajouté deux fois
    public function testVehicleIsNotDuplicated(): void
    {
        $fuelType = new FuelType();
        $vehicle = new Vehicle();

        $fuelType->addVehicle($vehicle);
        $fuelType->addVehicle($vehicle);

        $this->assertCount(1, $fuelType->getVehicles());
    }

    // vérifie la suppression d'un véhicule absent de la collection
    public function testRemoveVehicleNotInCollection(): void
    {
        $fuelType = new FuelType();
        $vehicle = new Vehicle();

        $fuelType->removeVehicle($vehicle);

        $this->assertCount(0, $fuelType->getVehicles());
    }

    // vérifie le cycle de vie complet de l'entité
    public function testCompleteFuelTypeLifecycle(): void
    {
        $fuelType = new FuelType();
        $vehicle = new Vehicle();

        $fuelType->setName('hybrid');

        $fuelType->addVehicle($vehicle);

        $this->assertSame('hybrid', $fuelType->getName());
        $this->assertCount(1, $fuelType->getVehicles());
        $this->assertSame($fuelType, $vehicle->getFuelType());
    }
}
