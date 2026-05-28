<?php

namespace App\Tests\Unit\Entity;

use App\Entity\BodyType;
use App\Entity\Brand;
use App\Entity\FuelType;
use App\Entity\GearType;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VehicleModelTest extends KernelTestCase
{
    private ValidatorInterface $validator;

protected function setUp(): void
{
    self::bootKernel();

    $this->validator = static::getContainer()->get(ValidatorInterface::class);
}

    // vérifie que l'id est null par défaut
    public function testIdIsNullByDefault(): void
    {
        $vehicleModel = new VehicleModel();

        $this->assertNull($vehicleModel->getId());
    }

    // vérifie la création de l'entité
    public function testVehicleModelCreation(): void
    {
        $vehicleModel = new VehicleModel();

        $this->assertInstanceOf(VehicleModel::class, $vehicleModel);
    }

    // vérifie que brand est obligatoire
    public function testBrandIsRequired(): void
    {
        $vehicleModel = new VehicleModel();

        $errors = $this->validator->validate($vehicleModel);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie le format euroNorm
    public function testEuroNormValidation(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setEuroNorm('invalid');

        $errors = $this->validator->validate($vehicleModel);

        $this->assertGreaterThan(0, count($errors));
    }

    // vérifie les relations null par défaut
    public function testRelationsAreNullByDefault(): void
    {
        $vehicleModel = new VehicleModel();

        $this->assertNull($vehicleModel->getBrand());
        $this->assertNull($vehicleModel->getModel());
        $this->assertNull($vehicleModel->getVariant());
        $this->assertNull($vehicleModel->getFuelType());
        $this->assertNull($vehicleModel->getGearType());
        $this->assertNull($vehicleModel->getBodyType());
    }

    // vérifie les setters des relations principales
    public function testSetRelations(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setBrand(new Brand());
        $vehicleModel->setModel(new Model());
        $vehicleModel->setVariant(new Variant());
        $vehicleModel->setFuelType(new FuelType());
        $vehicleModel->setGearType(new GearType());
        $vehicleModel->setBodyType(new BodyType());

        $this->assertInstanceOf(Brand::class, $vehicleModel->getBrand());
        $this->assertInstanceOf(Model::class, $vehicleModel->getModel());
        $this->assertInstanceOf(Variant::class, $vehicleModel->getVariant());
        $this->assertInstanceOf(FuelType::class, $vehicleModel->getFuelType());
        $this->assertInstanceOf(GearType::class, $vehicleModel->getGearType());
        $this->assertInstanceOf(BodyType::class, $vehicleModel->getBodyType());
    }

    // vérifie la collection vehicles vide par défaut
    public function testVehiclesCollectionIsEmptyByDefault(): void
    {
        $vehicleModel = new VehicleModel();

        $this->assertCount(0, $vehicleModel->getVehicles());
    }

    // vérifie l'ajout d'un vehicle
    public function testAddVehicle(): void
    {
        $vehicleModel = new VehicleModel();
        $vehicle = new Vehicle();

        $vehicleModel->addVehicle($vehicle);

        $this->assertTrue($vehicleModel->getVehicles()->contains($vehicle));
        $this->assertCount(1, $vehicleModel->getVehicles());
        $this->assertSame($vehicleModel, $vehicle->getVehicleModel());
    }

    // vérifie la suppression d'un vehicle
    public function testRemoveVehicle(): void
    {
        $vehicleModel = new VehicleModel();
        $vehicle = new Vehicle();

        $vehicleModel->addVehicle($vehicle);
        $vehicleModel->removeVehicle($vehicle);

        $this->assertCount(0, $vehicleModel->getVehicles());
        $this->assertNull($vehicle->getVehicleModel());
    }

    // vérifie qu'un vehicle ne peut pas être dupliqué
    public function testVehicleIsNotDuplicated(): void
    {
        $vehicleModel = new VehicleModel();
        $vehicle = new Vehicle();

        $vehicleModel->addVehicle($vehicle);
        $vehicleModel->addVehicle($vehicle);

        $this->assertCount(1, $vehicleModel->getVehicles());
    }

    // vérifie les champs techniques simples
    public function testTechnicalFields(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setEuroNorm('euro 6');
        $vehicleModel->setPowerHp(120);
        $vehicleModel->setPowerFiscal(6.5);
        $vehicleModel->setConsumption(5.2);
        $vehicleModel->setCo2(110);

        $this->assertSame('euro 6', $vehicleModel->getEuroNorm());
        $this->assertSame(120, $vehicleModel->getPowerHp());
        $this->assertSame(6.5, $vehicleModel->getPowerFiscal());
        $this->assertSame(5.2, $vehicleModel->getConsumption());
        $this->assertSame(110, $vehicleModel->getCo2());
    }

    // vérifie le comportement cnit en majuscules
    public function testCnitIsUppercased(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setCnit('ab123');

        $this->assertSame('AB123', $vehicleModel->getCnit());
    }

    // vérifie le comportement utac code en majuscules
    public function testUtacCodeIsUppercased(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setUtacCode('xyz789');

        $this->assertSame('XYZ789', $vehicleModel->getUtacCode());
    }

    // vérifie le display name
    public function testGetDisplayName(): void
    {
        $vehicleModel = new VehicleModel();

        $brand = new Brand();
        $model = new Model();
        $variant = new Variant();

        $brand->setName('bmw');
        $model->setName('serie 3');
        $variant->setName('m sport');

        $vehicleModel->setBrand($brand);
        $vehicleModel->setModel($model);
        $vehicleModel->setVariant($variant);

        $this->assertSame('bmw serie 3 m sport', $vehicleModel->getDisplayName());
    }

    // vérifie la méthode __toString
    public function testToString(): void
    {
        $vehicleModel = new VehicleModel();

        $brand = new Brand();
        $model = new Model();

        $brand->setName('audi');
        $model->setName('a4');

        $vehicleModel->setBrand($brand);
        $vehicleModel->setModel($model);

        $this->assertSame('audi a4', (string) $vehicleModel);
    }

    // vérifie les stocks par défaut
    public function testStockDefaults(): void
    {
        $vehicleModel = new VehicleModel();

        $this->assertSame(0, $vehicleModel->getAvailableStock());
        $this->assertSame(0, $vehicleModel->getAvailableForSale());
        $this->assertSame(0, $vehicleModel->getAvailableForRent());
    }

    // vérifie les setters de stock
    public function testStockSetters(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setAvailableStock(10);
        $vehicleModel->setAvailableForSale(5);
        $vehicleModel->setAvailableForRent(3);

        $this->assertSame(10, $vehicleModel->getAvailableStock());
        $this->assertSame(5, $vehicleModel->getAvailableForSale());
        $this->assertSame(3, $vehicleModel->getAvailableForRent());
    }
    // vérifie que getDisplayName fonctionne sans variant
    public function testGetDisplayNameWithoutVariant(): void
    {
        $vehicleModel = new VehicleModel();

        $brand = new Brand();
        $model = new Model();

        $brand->setName('audi');
        $model->setName('a3');

        $vehicleModel->setBrand($brand);
        $vehicleModel->setModel($model);

        $this->assertSame('audi a3', $vehicleModel->getDisplayName());
    }

    // vérifie que le displayName est vide sans relations
    public function testGetDisplayNameWithoutRelations(): void
    {
        $vehicleModel = new VehicleModel();

        $this->assertSame('', $vehicleModel->getDisplayName());
    }

    // vérifie setCnit avec null
    public function testSetCnitWithNull(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setCnit(null);

        $this->assertNull($vehicleModel->getCnit());
    }

    // vérifie setUtacCode avec null
    public function testSetUtacCodeWithNull(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setUtacCode(null);

        $this->assertNull($vehicleModel->getUtacCode());
    }

    // vérifie homologationDate
    public function testHomologationDate(): void
    {
        $vehicleModel = new VehicleModel();

        $date = new \DateTime();

        $vehicleModel->setHomologationDate($date);

        $this->assertSame($date, $vehicleModel->getHomologationDate());
    }

    // vérifie massMin
    public function testMassMin(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setMassMin(1200);

        $this->assertSame(1200, $vehicleModel->getMassMin());
    }

    // vérifie massMax
    public function testMassMax(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setMassMax(1800);

        $this->assertSame(1800, $vehicleModel->getMassMax());
    }

    // vérifie la suppression d'un véhicule absent
    public function testRemoveVehicleNotInCollection(): void
    {
        $vehicleModel = new VehicleModel();
        $vehicle = new Vehicle();

        $vehicleModel->removeVehicle($vehicle);

        $this->assertCount(0, $vehicleModel->getVehicles());
    }

    // vérifie les setters nullable
    public function testNullableRelations(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicleModel->setVariant(null);
        $vehicleModel->setFuelType(null);
        $vehicleModel->setGearType(null);

        $this->assertNull($vehicleModel->getVariant());
        $this->assertNull($vehicleModel->getFuelType());
        $this->assertNull($vehicleModel->getGearType());
    }

    // vérifie le cycle de vie complet
    public function testCompleteLifecycle(): void
    {
        $vehicleModel = new VehicleModel();

        $vehicle = new Vehicle();
        $brand = new Brand();
        $model = new Model();
        $variant = new Variant();
        $gearType = new GearType();
        $bodyType = new BodyType();

        $vehicleModel->setBrand($brand);
        $vehicleModel->setModel($model);
        $vehicleModel->setVariant($variant);
        $vehicleModel->setGearType($gearType);
        $vehicleModel->setBodyType($bodyType);

        $vehicleModel->addVehicle($vehicle);

        $this->assertSame($brand, $vehicleModel->getBrand());
        $this->assertSame($model, $vehicleModel->getModel());
        $this->assertSame($variant, $vehicleModel->getVariant());
        $this->assertSame($gearType, $vehicleModel->getGearType());
        $this->assertSame($bodyType, $vehicleModel->getBodyType());

        $this->assertCount(1, $vehicleModel->getVehicles());
        $this->assertSame($vehicleModel, $vehicle->getVehicleModel());
    }
}
