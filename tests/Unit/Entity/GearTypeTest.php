<?php

namespace App\Tests\Unit\Entity;

use App\Entity\GearType;
use App\Entity\VehicleModel;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GearTypeTest extends KernelTestCase
{
    // On ajoute le validator Symfony
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testBodyTypeCreation(): void
    {
        $gearType = new GearType();
        $this->assertInstanceOf(GearType::class, $gearType, "Correctement instancié");
    }

    public function testNameIsNullByDefault(): void
    {
        $gearType = new GearType();
        $this->assertNull($gearType->getName());
    }

    public function testSetName(): void
    {
        $gearType = new GearType();
        $gearType->setName('name of the gearType');

        $this->assertSame('name of the gearType', $gearType->getName());
    }

    public function testNameCannotBeBlank(): void
    {
        $gearType = new GearType();
        //Attention à mettre un espace vide dans la parenthèse:
        $gearType->setName(' ');

        $errors = $this->validator->validate($gearType);

        // 2 Contraintes sont vérifiées : notblank et length min
        $this->assertCount(2, $errors);
    }

    public function testNameIsTooShort(): void
    {
        $gearType = new GearType();

        $gearType->setName('a');
        $errors = $this->validator->validate($gearType);
        $this->assertGreaterThan(0, count($errors));
    }
    public function testNameIsTooLong(): void
    {
        $gearType = new GearType();

        $gearType->setName(str_repeat('a', 121));
        $errors = $this->validator->validate($gearType);
        $this->assertGreaterThan(0, count($errors));
    }



    //Tests concernant la collection vehicleModel

    //Vérification que la collection est vide
    public function testVehicleModelsCollectionIsEmptyByDefault(): void
    {
        $gearType = new GearType();

        //On vérifie que vehicleModels soit bien une collection
        $this->assertInstanceOf(Collection::class, $gearType->getVehicleModels());
        //On vérifie que la collection est vide au départ
        $this->assertCount(0, $gearType->getVehicleModels());
    }

    public function testAddVehicleModel(): void
    {
        $gearType = new GearType();
        $vehicleModel = new VehicleModel();

        $gearType->addVehicleModel($vehicleModel);

        //On vérifie la présence dans la collection
        $this->assertTrue($gearType->getVehicleModels()->contains($vehicleModel));

        $this->assertCount(1, $gearType->getVehicleModels());

        //On verifie que la relation bidirectionnelle fonctionne
        $this->assertSame($gearType, $vehicleModel->getGearType());
    }

    public function testVehicleModelIsNotDuplicated(): void
    {
        $gearType = new GearType();

        $vehicleModel = new VehicleModel();

        //On crée 2 fois un vehicleModel
        $gearType->addVehicleModel($vehicleModel);
        $gearType->addVehicleModel($vehicleModel);

        //On vérifie que l'instance soit bonne dans la collection
        $this->assertTrue($gearType->getVehicleModels()->contains($vehicleModel));

        //On vérifie qu'un seul vehicleModel est généré
        $this->assertCount(1, $gearType->getVehicleModels());
    }

    public function testRemoveVehicleModel(): void
    {
        $gearType = new GearType();
        $vehicleModel = new VehicleModel();
        $gearType->addVehicleModel($vehicleModel);
        $gearType->removeVehicleModel($vehicleModel);

        $this->assertCount(0, $gearType->getVehicleModels());

        //On vérifie la relation inverse
        $this->assertNull($vehicleModel->getBodyType());
    }
}
