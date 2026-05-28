<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Model;
use App\Entity\Brand;
use App\Entity\Variant;
use App\Entity\VehicleModel;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ModelTest extends KernelTestCase
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
        $model = new Model();

        $this->assertNull($model->getId());
    }

    // vérifie la création de l'entité model
    public function testModelCreation(): void
    {
        $model = new Model();

        $this->assertInstanceOf(Model::class, $model);
    }

    // vérifie que le name est null par défaut
    public function testNameIsNullByDefault(): void
    {
        $model = new Model();

        $this->assertNull($model->getName());
    }

    // vérifie le setter de name
    public function testSetName(): void
    {
        $model = new Model();

        $model->setName('test model');

        $this->assertSame('test model', $model->getName());
    }

    // vérifie que le name ne peut pas être vide
    public function testNameCannotBeBlank(): void
    {
        $model = new Model();

        $model->setName(' ');

        $errors = $this->validator->validate($model);

        $this->assertGreaterThanOrEqual(1, count($errors));
    }

    // vérifie que le name est trop court
    public function testNameIsTooShort(): void
    {
        $model = new Model();

        $model->setName('a');

        $errors = $this->validator->validate($model);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que le name est trop long
    public function testNameIsTooLong(): void
    {
        $model = new Model();

        $model->setName(str_repeat('a', 121));

        $errors = $this->validator->validate($model);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie la relation brand par défaut
    public function testBrandIsNullByDefault(): void
    {
        $model = new Model();

        $this->assertNull($model->getBrand());
    }

    // vérifie le setter brand
    public function testSetBrand(): void
    {
        $model = new Model();
        $brand = new Brand();

        $model->setBrand($brand);

        $this->assertSame($brand, $model->getBrand());
    }

    // vérifie que la collection variants est vide par défaut
    public function testVariantsCollectionIsEmptyByDefault(): void
    {
        $model = new Model();

        $this->assertInstanceOf(Collection::class, $model->getVariants());
        $this->assertCount(0, $model->getVariants());
    }

    // vérifie l'ajout d'un variant
    public function testAddVariant(): void
    {
        $model = new Model();
        $variant = new Variant();

        $model->addVariant($variant);

        $this->assertTrue($model->getVariants()->contains($variant));
        $this->assertCount(1, $model->getVariants());
        $this->assertSame($model, $variant->getModel());
    }

    // vérifie la suppression d'un variant
    public function testRemoveVariant(): void
    {
        $model = new Model();
        $variant = new Variant();

        $model->addVariant($variant);
        $model->removeVariant($variant);

        $this->assertCount(0, $model->getVariants());
        $this->assertNull($variant->getModel());
    }

    // vérifie qu'un variant ne peut pas être dupliqué
    public function testVariantIsNotDuplicated(): void
    {
        $model = new Model();
        $variant = new Variant();

        $model->addVariant($variant);
        $model->addVariant($variant);

        $this->assertCount(1, $model->getVariants());
    }

    // vérifie la collection vehicleModels vide par défaut
    public function testVehicleModelsCollectionIsEmptyByDefault(): void
    {
        $model = new Model();

        $this->assertInstanceOf(Collection::class, $model->getVehicleModels());
        $this->assertCount(0, $model->getVehicleModels());
    }

    // vérifie l'ajout d'un vehicleModel
    public function testAddVehicleModel(): void
    {
        $model = new Model();
        $vehicleModel = new VehicleModel();

        $model->addVehicleModel($vehicleModel);

        $this->assertTrue($model->getVehicleModels()->contains($vehicleModel));
        $this->assertCount(1, $model->getVehicleModels());
        $this->assertSame($model, $vehicleModel->getModel());
    }

    // vérifie la suppression d'un vehicleModel
    public function testRemoveVehicleModel(): void
    {
        $model = new Model();
        $vehicleModel = new VehicleModel();

        $model->addVehicleModel($vehicleModel);
        $model->removeVehicleModel($vehicleModel);

        $this->assertCount(0, $model->getVehicleModels());
        $this->assertNull($vehicleModel->getModel());
    }

    // vérifie qu'un vehicleModel ne peut pas être dupliqué
    public function testVehicleModelIsNotDuplicated(): void
    {
        $model = new Model();
        $vehicleModel = new VehicleModel();

        $model->addVehicleModel($vehicleModel);
        $model->addVehicleModel($vehicleModel);

        $this->assertCount(1, $model->getVehicleModels());
    }

    // vérifie le cycle de vie complet de l'entité model
    public function testCompleteModelLifecycle(): void
    {
        $model = new Model();
        $brand = new Brand();
        $variant = new Variant();
        $vehicleModel = new VehicleModel();

        $model->setName('test');
        $model->setBrand($brand);

        $model->addVariant($variant);
        $model->addVehicleModel($vehicleModel);

        $this->assertSame('test', $model->getName());
        $this->assertSame($brand, $model->getBrand());

        $this->assertCount(1, $model->getVariants());
        $this->assertCount(1, $model->getVehicleModels());

        $this->assertSame($model, $variant->getModel());
        $this->assertSame($model, $vehicleModel->getModel());
    }
}
