<?php

namespace App\Tests\Unit\Entity;

use App\Entity\GearType;
use App\Entity\VehicleModel;
use App\Entity\Vehicle;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GearTypeTest extends KernelTestCase
{
    // on ajoute le validator symfony
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    // vérifie que l'id est null par défaut
    public function testIdIsNullByDefault(): void
    {
        $gearType = new GearType();

        $this->assertNull($gearType->getId());
    }

    // vérifie la création de l'entité
    public function testBodyTypeCreation(): void
    {
        $gearType = new GearType();

        $this->assertInstanceOf(GearType::class, $gearType);
    }

    // vérifie que le nom est null par défaut
    public function testNameIsNullByDefault(): void
    {
        $gearType = new GearType();

        $this->assertNull($gearType->getName());
    }

    // vérifie le setter de name
    public function testSetName(): void
    {
        $gearType = new GearType();

        $gearType->setName('name of the gearType');

        $this->assertSame('name of the gearType', $gearType->getName());
    }

    // vérifie que les espaces sont supprimés dans le setter
    public function testNameIsTrimmed(): void
    {
        $gearType = new GearType();

        $gearType->setName('  automatique  ');

        $this->assertSame('automatique', $gearType->getName());
    }

    // vérifie que le name ne peut pas être vide
    public function testNameCannotBeBlank(): void
    {
        $gearType = new GearType();

        $gearType->setName(' ');

        $errors = $this->validator->validate($gearType);

        $this->assertGreaterThanOrEqual(1, count($errors));
    }

    // vérifie que le name est trop court
    public function testNameIsTooShort(): void
    {
        $gearType = new GearType();

        $gearType->setName('a');

        $errors = $this->validator->validate($gearType);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que le name est trop long
    public function testNameIsTooLong(): void
    {
        $gearType = new GearType();

        $gearType->setName(str_repeat('a', 121));

        $errors = $this->validator->validate($gearType);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que les caractères invalides sont rejetés
    public function testNameRejectsInvalidCharacters(): void
    {
        $gearType = new GearType();

        $gearType->setName('@@@###');

        $errors = $this->validator->validate($gearType);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que la collection vehicles est vide par défaut
    public function testVehiclesCollectionIsEmptyByDefault(): void
    {
        $gearType = new GearType();

        $this->assertInstanceOf(Collection::class, $gearType->getVehicles());
        $this->assertCount(0, $gearType->getVehicles());
    }

    // vérifie l'ajout d'un vehicle
    public function testAddVehicle(): void
    {
        $gearType = new GearType();
        $vehicle = new Vehicle();

        $gearType->addVehicle($vehicle);

        $this->assertTrue($gearType->getVehicles()->contains($vehicle));
        $this->assertCount(1, $gearType->getVehicles());
        $this->assertSame($gearType, $vehicle->getGearType());
    }

    // vérifie la suppression d'un vehicle
    public function testRemoveVehicle(): void
    {
        $gearType = new GearType();
        $vehicle = new Vehicle();

        $gearType->addVehicle($vehicle);
        $gearType->removeVehicle($vehicle);

        $this->assertCount(0, $gearType->getVehicles());
        $this->assertNull($vehicle->getGearType());
    }

    // vérifie qu'un vehicle ne peut pas être dupliqué
    public function testVehicleIsNotDuplicated(): void
    {
        $gearType = new GearType();
        $vehicle = new Vehicle();

        $gearType->addVehicle($vehicle);
        $gearType->addVehicle($vehicle);

        $this->assertCount(1, $gearType->getVehicles());
    }

    // vérifie que la collection vehicleModels est vide par défaut
    public function testVehicleModelsCollectionIsEmptyByDefault(): void
    {
        $gearType = new GearType();

        $this->assertInstanceOf(Collection::class, $gearType->getVehicleModels());
        $this->assertCount(0, $gearType->getVehicleModels());
    }

    // vérifie l'ajout d'un vehicleModel
    public function testAddVehicleModel(): void
    {
        $gearType = new GearType();
        $vehicleModel = new VehicleModel();

        $gearType->addVehicleModel($vehicleModel);

        $this->assertTrue($gearType->getVehicleModels()->contains($vehicleModel));
        $this->assertCount(1, $gearType->getVehicleModels());
        $this->assertSame($gearType, $vehicleModel->getGearType());
    }

    // vérifie la suppression d'un vehicleModel
    public function testRemoveVehicleModel(): void
    {
        $gearType = new GearType();
        $vehicleModel = new VehicleModel();

        $gearType->addVehicleModel($vehicleModel);
        $gearType->removeVehicleModel($vehicleModel);

        $this->assertCount(0, $gearType->getVehicleModels());
        $this->assertNull($vehicleModel->getGearType());
    }

    // vérifie qu'un vehicleModel ne peut pas être dupliqué
    public function testVehicleModelIsNotDuplicated(): void
    {
        $gearType = new GearType();
        $vehicleModel = new VehicleModel();

        $gearType->addVehicleModel($vehicleModel);
        $gearType->addVehicleModel($vehicleModel);

        $this->assertCount(1, $gearType->getVehicleModels());
    }

    // vérifie le cycle de vie complet de l'entité
    public function testCompleteGearTypeLifecycle(): void
    {
        $gearType = new GearType();
        $vehicle = new Vehicle();
        $vehicleModel = new VehicleModel();

        $gearType->setName('manual');

        $gearType->addVehicle($vehicle);
        $gearType->addVehicleModel($vehicleModel);

        $this->assertSame('manual', $gearType->getName());
        $this->assertCount(1, $gearType->getVehicles());
        $this->assertCount(1, $gearType->getVehicleModels());
        $this->assertSame($gearType, $vehicle->getGearType());
        $this->assertSame($gearType, $vehicleModel->getGearType());
    }
}