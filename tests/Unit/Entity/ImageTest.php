<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Image;
use App\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $image = new Image();

        self::assertNull($image->getId());
        self::assertNull($image->getVehicle());
        self::assertNull($image->getFilename());
        self::assertNull($image->getThumbnail());
        self::assertNull($image->getOriginalName());
        self::assertNull($image->getMimeType());
        self::assertNull($image->getSize());
        self::assertNull($image->getImagePath());

        self::assertSame(
            0,
            $image->getPosition()
        );

        self::assertFalse(
            $image->isFeatured()
        );
    }

    // vérifie l'association véhicule
    public function testVehicle(): void
    {
        $image = new Image();
        $vehicle = new Vehicle();

        $image->setVehicle($vehicle);

        self::assertSame(
            $vehicle,
            $image->getVehicle()
        );
    }

    // vérifie qu'un véhicule peut être null
    public function testVehicleCanBeNull(): void
    {
        $image = new Image();

        $image->setVehicle(null);

        self::assertNull(
            $image->getVehicle()
        );
    }

    // vérifie le nom du fichier
    public function testFilename(): void
    {
        $image = new Image();

        $image->setFilename('image.jpg');

        self::assertSame(
            'image.jpg',
            $image->getFilename()
        );
    }

    // vérifie la miniature
    public function testThumbnail(): void
    {
        $image = new Image();

        $image->setThumbnail('thumb.jpg');

        self::assertSame(
            'thumb.jpg',
            $image->getThumbnail()
        );
    }

    // vérifie le nom original
    public function testOriginalName(): void
    {
        $image = new Image();

        $image->setOriginalName('photo-vehicule.jpg');

        self::assertSame(
            'photo-vehicule.jpg',
            $image->getOriginalName()
        );
    }

    // vérifie le type mime
    public function testMimeType(): void
    {
        $image = new Image();

        $image->setMimeType('image/jpeg');

        self::assertSame(
            'image/jpeg',
            $image->getMimeType()
        );
    }

    // vérifie la taille
    public function testSize(): void
    {
        $image = new Image();

        $image->setSize(1024000);

        self::assertSame(
            1024000,
            $image->getSize()
        );
    }

    // vérifie la position
    public function testPosition(): void
    {
        $image = new Image();

        $image->setPosition(5);

        self::assertSame(
            5,
            $image->getPosition()
        );
    }

    // vérifie l'état featured
    public function testFeaturedFlag(): void
    {
        $image = new Image();

        $image->setIsFeatured(true);

        self::assertTrue(
            $image->isFeatured()
        );

        $image->setIsFeatured(false);

        self::assertFalse(
            $image->isFeatured()
        );
    }

    // vérifie le chemin de l'image
    public function testImagePath(): void
    {
        $image = new Image();

        $image->setImagePath(
            '/uploads/vehicles/test.jpg'
        );

        self::assertSame(
            '/uploads/vehicles/test.jpg',
            $image->getImagePath()
        );
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $image = new Image();

        self::assertSame(
            $image,
            $image->setVehicle(new Vehicle())
        );

        self::assertSame(
            $image,
            $image->setFilename('image.jpg')
        );

        self::assertSame(
            $image,
            $image->setThumbnail('thumb.jpg')
        );

        self::assertSame(
            $image,
            $image->setOriginalName('photo.jpg')
        );

        self::assertSame(
            $image,
            $image->setMimeType('image/jpeg')
        );

        self::assertSame(
            $image,
            $image->setSize(1000)
        );

        self::assertSame(
            $image,
            $image->setPosition(1)
        );

        self::assertSame(
            $image,
            $image->setIsFeatured(true)
        );

        self::assertSame(
            $image,
            $image->setImagePath('/uploads/test.jpg')
        );
    }
}
