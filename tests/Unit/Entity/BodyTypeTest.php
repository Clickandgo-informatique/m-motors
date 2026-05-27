<?php

namespace App\Tests\Unit\Entity;

use App\Entity\BodyType;
use App\Entity\VehicleModel;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BodyTypeTest extends KernelTestCase
{
    // On ajoute le validator Symfony
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    //Test pour vérifier que l'id est bien null par défaut
    public function testIdIsNullByDefault(): void
{
    $bodyType = new BodyType();
    $this->assertNull($bodyType->getId());
}

    public function testBodyTypeCreation(): void
    {
        $bodyType = new BodyType();
        $this->assertInstanceOf(BodyType::class, $bodyType, "Correctement instancié");
    }

    public function testNameIsNullByDefault(): void
    {
        $bodyType = new BodyType();
        $this->assertNull($bodyType->getName());
    }

    public function testSetName(): void
    {
        $bodyType = new BodyType();
        $bodyType->setName('name of the bodyType');

        $this->assertSame('name of the bodyType', $bodyType->getName());
    }

    public function testNameCannotBeBlank(): void
    {
        $bodyType = new BodyType();
        //Attention à mettre un espace vide dans la parenthèse:
        $bodyType->setName(' ');

        $errors = $this->validator->validate($bodyType);

        // 2 Contraintes sont vérifiées : notblank et length min
        $this->assertCount(2, $errors);
    }

    //Test sur la longueur trop courte du champ "name"
    public function testNameIsTooShort(): void
    {
        $bodyType = new BodyType();

        $bodyType->setName('a');
        $errors = $this->validator->validate($bodyType);
        $this->assertGreaterThan(0, count($errors));
    }

    //Test sur la longueur trop longue du champ "name"
    public function testNameIsTooLong(): void
    {
        $bodyType = new BodyType();

        $bodyType->setName(str_repeat('a', 121));
        $errors = $this->validator->validate($bodyType);
        $this->assertGreaterThan(0, count($errors));
    }



    //Tests concernant la collection vehicleModel

    //Vérification que la collection est vide
    public function testVehicleModelsCollectionIsEmptyByDefault(): void
    {
        $bodyType = new BodyType();

        //On vérifie que vehicleModels soit bien une collection
        $this->assertInstanceOf(Collection::class, $bodyType->getVehicleModels());
        //On vérifie que la collection est vide au départ
        $this->assertCount(0, $bodyType->getVehicleModels());
    }

    public function testAddVehicleModel(): void
    {
        $bodyType = new BodyType();
        $vehicleModel = new VehicleModel();

        $bodyType->addVehicleModel($vehicleModel);

        //On vérifie la présence dans la collection
        $this->assertTrue($bodyType->getVehicleModels()->contains($vehicleModel));

        $this->assertCount(1, $bodyType->getVehicleModels());

        //On verifie que la relation bidirectionnelle fonctionne
        $this->assertSame($bodyType, $vehicleModel->getBodyType());
    }

    public function testVehicleModelIsNotDuplicated(): void
    {
        $bodyType = new BodyType();

        $vehicleModel = new VehicleModel();

        //On crée 2 fois un vehicleModel
        $bodyType->addVehicleModel($vehicleModel);
        $bodyType->addVehicleModel($vehicleModel);

        //On vérifie que l'instance soit bonne dans la collection
        $this->assertTrue($bodyType->getVehicleModels()->contains($vehicleModel));

        //On vérifie qu'un seul vehicleModel est généré
        $this->assertCount(1, $bodyType->getVehicleModels());
    }

    public function testRemoveVehicleModel(): void
    {
        $bodyType = new BodyType();
        $vehicleModel = new VehicleModel();
        $bodyType->addVehicleModel($vehicleModel);
        $bodyType->removeVehicleModel($vehicleModel);

        $this->assertCount(0, $bodyType->getVehicleModels());

        //On vérifie la relation inverse
        $this->assertNull($vehicleModel->getBodyType());
    }

    //test concernant le slug
    public function testSetAndGetSlug(): void
    {
        $bodyType = new BodyType();
        $bodyType->setSlug('my-slug');

        $this->assertSame('my-slug', $bodyType->getSlug());
    }


    //test concernant la position
    public function testSetAndGetPosition(): void
    {
        $bodyType = new BodyType();
        $bodyType->setPosition(5);

        $this->assertSame(5, $bodyType->getPosition());
    }

    //test concernant l'icône
    public function testSetAndGetIcon(): void
    {
        $bodyType = new BodyType();
        $bodyType->setIcon('car.png');

        $this->assertSame('car.png', $bodyType->getIcon());
    }

    //Test __toString() quand name est null
    public function testToStringReturnsEmptyStringWhenNameIsNull(): void
    {
        $bodyType = new BodyType();
        $this->assertSame('', (string) $bodyType);
    }
    //Test addVehicleModel quand le modèle a déjà un BodyType
    public function testAddVehicleModelWhenAlreadyAssigned(): void
    {
        $bodyType1 = new BodyType();
        $bodyType2 = new BodyType();
        $vehicleModel = new VehicleModel();

        $bodyType1->addVehicleModel($vehicleModel);

        // On réassigne
        $bodyType2->addVehicleModel($vehicleModel);

        $this->assertFalse($bodyType1->getVehicleModels()->contains($vehicleModel));
        $this->assertTrue($bodyType2->getVehicleModels()->contains($vehicleModel));
        $this->assertSame($bodyType2, $vehicleModel->getBodyType());
    }

    //Tester removeVehicleModel quand il n’est pas dans la collection
    public function testRemoveVehicleModelWhenNotPresent(): void
    {
        $bodyType = new BodyType();
        $vehicleModel = new VehicleModel();

        // Ne doit pas planter
        $bodyType->removeVehicleModel($vehicleModel);

        $this->assertCount(0, $bodyType->getVehicleModels());
    }
}
