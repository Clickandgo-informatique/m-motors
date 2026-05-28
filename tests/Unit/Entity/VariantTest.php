<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Variant;
use App\Entity\Model;
use App\Entity\VehicleModel;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VariantTest extends KernelTestCase
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
        $variant = new Variant();

        $this->assertNull($variant->getId());
    }

    // vérifie la création de l'entité variant
    public function testVariantCreation(): void
    {
        $variant = new Variant();

        $this->assertInstanceOf(Variant::class, $variant);
    }

    // vérifie que le name est null par défaut
    public function testNameIsNullByDefault(): void
    {
        $variant = new Variant();

        $this->assertNull($variant->getName());
    }

    // vérifie le setter name
    public function testSetName(): void
    {
        $variant = new Variant();

        $variant->setName('test variant');

        $this->assertSame('test variant', $variant->getName());
    }

    // vérifie que le name ne peut pas être vide
    public function testNameCannotBeBlank(): void
    {
        $variant = new Variant();

        $variant->setName(' ');

        $errors = $this->validator->validate($variant);

        $this->assertGreaterThanOrEqual(1, count($errors));
    }

    // vérifie que le name est trop court
    public function testNameIsTooShort(): void
    {
        $variant = new Variant();

        $variant->setName('a');

        $errors = $this->validator->validate($variant);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie que le name est trop long
    public function testNameIsTooLong(): void
    {
        $variant = new Variant();

        $variant->setName(str_repeat('a', 256));

        $errors = $this->validator->validate($variant);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie la relation model par défaut
    public function testModelIsNullByDefault(): void
    {
        $variant = new Variant();

        $this->assertNull($variant->getModel());
    }

    // vérifie le setter model
    public function testSetModel(): void
    {
        $variant = new Variant();
        $model = new Model();

        $variant->setModel($model);

        $this->assertSame($model, $variant->getModel());
    }

    // vérifie que la collection vehicleModels est vide par défaut
    public function testVehicleModelsCollectionIsEmptyByDefault(): void
    {
        $variant = new Variant();

        $this->assertInstanceOf(Collection::class, $variant->getVehicleModels());
        $this->assertCount(0, $variant->getVehicleModels());
    }

    // vérifie l'ajout d'un vehicleModel
    public function testAddVehicleModel(): void
    {
        $variant = new Variant();
        $vehicleModel = new VehicleModel();

        $variant->addVehicleModel($vehicleModel);

        $this->assertTrue($variant->getVehicleModels()->contains($vehicleModel));
        $this->assertCount(1, $variant->getVehicleModels());
        $this->assertSame($variant, $vehicleModel->getVariant());
    }

    // vérifie la suppression d'un vehicleModel
    public function testRemoveVehicleModel(): void
    {
        $variant = new Variant();
        $vehicleModel = new VehicleModel();

        $variant->addVehicleModel($vehicleModel);
        $variant->removeVehicleModel($vehicleModel);

        $this->assertCount(0, $variant->getVehicleModels());
        $this->assertNull($vehicleModel->getVariant());
    }

    // vérifie qu'un vehicleModel ne peut pas être dupliqué
    public function testVehicleModelIsNotDuplicated(): void
    {
        $variant = new Variant();
        $vehicleModel = new VehicleModel();

        $variant->addVehicleModel($vehicleModel);
        $variant->addVehicleModel($vehicleModel);

        $this->assertCount(1, $variant->getVehicleModels());
    }

    // vérifie le cycle de vie complet de l'entité variant
    public function testCompleteVariantLifecycle(): void
    {
        $variant = new Variant();
        $model = new Model();
        $vehicleModel = new VehicleModel();

        $variant->setName('test');
        $variant->setModel($model);

        $variant->addVehicleModel($vehicleModel);

        $this->assertSame('test', $variant->getName());
        $this->assertSame($model, $variant->getModel());
        $this->assertCount(1, $variant->getVehicleModels());
        $this->assertSame($variant, $vehicleModel->getVariant());
    }
}
