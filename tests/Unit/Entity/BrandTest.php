<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Brand;
use App\Entity\VehicleModel;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BrandTest extends KernelTestCase
{
    // On ajoute le validator Symfony
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testBrandCreation(): void
    {
        $brand = new Brand();
        $this->assertInstanceOf(Brand::class, $brand, "Correctement instancié");
    }

    public function testNameIsNullByDefault(): void
    {
        $brand = new Brand();
        $this->assertNull($brand->getName());
    }

    public function testSetName(): void
    {
        $brand = new Brand();
        $brand->setName('name of the brand');

        $this->assertSame('name of the brand', $brand->getName());
    }

    public function testNameCannotBeBlank(): void
    {
        $brand = new Brand();
        //Attention à mettre un espace vide dans la parenthèse:
        $brand->setName(' ');

        $errors = $this->validator->validate($brand);

        $this->assertCount(1, $errors);
    }

    public function testNameIsTooShort(): void
    {
        $brand = new Brand();

        $brand->setName('a');
        $errors = $this->validator->validate($brand);
        $this->assertGreaterThan(0, count($errors));
    }
    public function testNameIsTooLong(): void
    {
        $brand = new Brand();

        $brand->setName(str_repeat('a', 121));
        $errors = $this->validator->validate($brand);
        $this->assertGreaterThan(0, count($errors));
    }

    //Tests concernant la collection vehicleModel

    //Vérification que la collection est vide
    public function testVehicleModelsCollectionIsEmptyByDefault(): void
    {
        $brand = new Brand();

        //On vérifie que vehicleModels soit bien une collection
        $this->assertInstanceOf(Collection::class, $brand->getVehicleModels());
        //On vérifie que la collection est vide au départ
        $this->assertCount(0, $brand->getVehicleModels());
    }

    public function testAddVehicleModel(): void
    {
        $brand = new Brand();
        $vehicleModel = new VehicleModel();

        $brand->addVehicleModel($vehicleModel);

        //On vérifie la présence dans la collection
        $this->assertTrue($brand->getVehicleModels()->contains($vehicleModel));

        $this->assertCount(1, $brand->getVehicleModels());

        //On verifie que la relation bidirectionnelle fonctionne
        $this->assertSame($brand, $vehicleModel->getBrand());
    }

    public function testVehicleModelIsNotDuplicated(): void
    {
        $brand = new Brand();

        $vehicleModel = new VehicleModel();

        //On crée 2 fois un vehicleModel
        $brand->addVehicleModel($vehicleModel);
        $brand->addVehicleModel($vehicleModel);

        //On vérifie que l'instance soit bonne dans la collection
        $this->assertTrue($brand->getVehicleModels()->contains($vehicleModel));

        //On vérifie qu'un seul vehicleModel est généré
        $this->assertCount(1, $brand->getVehicleModels());
    }

    public function testRemoveVehicleModel(): void
    {
        $brand = new Brand();
        $vehicleModel = new VehicleModel();
        $brand->addVehicleModel($vehicleModel);
        $brand->removeVehicleModel($vehicleModel);

        $this->assertCount(0, $brand->getVehicleModels());

        //On vérifie la relation inverse
        $this->assertNull($vehicleModel->getBrand());
    }
}
