<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Brand;
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
}
