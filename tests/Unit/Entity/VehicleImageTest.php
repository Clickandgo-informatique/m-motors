<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Image;
use App\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

class VehicleImageTest extends TestCase
{
    // vérifie l'ajout d'une image
    public function testAddImage(): void
    {
        $vehicle = new Vehicle();
        $image = new Image();

        $vehicle->addImage($image);

        self::assertCount(
            1,
            $vehicle->getImages()
        );

        self::assertTrue(
            $vehicle->getImages()->contains($image)
        );

        self::assertSame(
            $vehicle,
            $image->getVehicle()
        );
    }

    // vérifie qu'une image ne peut pas être ajoutée deux fois
    public function testAddImageOnlyOnce(): void
    {
        $vehicle = new Vehicle();
        $image = new Image();

        $vehicle->addImage($image);
        $vehicle->addImage($image);

        self::assertCount(
            1,
            $vehicle->getImages()
        );
    }

    // vérifie la suppression d'une image
    public function testRemoveImage(): void
    {
        $vehicle = new Vehicle();
        $image = new Image();

        $vehicle->addImage($image);

        self::assertCount(
            1,
            $vehicle->getImages()
        );

        $vehicle->removeImage($image);

        self::assertCount(
            0,
            $vehicle->getImages()
        );

        self::assertNull(
            $image->getVehicle()
        );
    }

    // vérifie la suppression d'une image absente
    public function testRemoveUnknownImage(): void
    {
        $vehicle = new Vehicle();
        $image = new Image();

        $vehicle->removeImage($image);

        self::assertCount(
            0,
            $vehicle->getImages()
        );
    }

    // vérifie l'image par défaut lorsqu'aucune image n'existe
    public function testGetMainImagePathReturnsDefaultImage(): void
    {
        $vehicle = new Vehicle();

        self::assertSame(
            'uploads/vehicles/default-vehicle.png',
            $vehicle->getMainImagePath()
        );
    }

    // vérifie le retour de la première image disponible
    public function testGetMainImagePathReturnsFirstImage(): void
    {
        $vehicle = new Vehicle();

        $image1 = (new Image())
            ->setImagePath('uploads/image-1.jpg');

        $image2 = (new Image())
            ->setImagePath('uploads/image-2.jpg');

        $vehicle->addImage($image1);
        $vehicle->addImage($image2);

        self::assertSame(
            'uploads/image-1.jpg',
            $vehicle->getMainImagePath()
        );
    }

    // vérifie qu'une image mise en avant est prioritaire
    public function testGetMainImagePathReturnsFeaturedImage(): void
    {
        $vehicle = new Vehicle();

        $image1 = (new Image())
            ->setImagePath('uploads/image-1.jpg');

        $image2 = (new Image())
            ->setImagePath('uploads/featured.jpg')
            ->setIsFeatured(true);

        $vehicle->addImage($image1);
        $vehicle->addImage($image2);

        self::assertSame(
            'uploads/featured.jpg',
            $vehicle->getMainImagePath()
        );
    }

    // vérifie que la première image mise en avant est utilisée
    public function testGetMainImagePathReturnsFirstFeaturedImage(): void
    {
        $vehicle = new Vehicle();

        $image1 = (new Image())
            ->setImagePath('uploads/featured-1.jpg')
            ->setIsFeatured(true);

        $image2 = (new Image())
            ->setImagePath('uploads/featured-2.jpg')
            ->setIsFeatured(true);

        $vehicle->addImage($image1);
        $vehicle->addImage($image2);

        self::assertSame(
            'uploads/featured-1.jpg',
            $vehicle->getMainImagePath()
        );
    }

    // vérifie le chaînage addImage
    public function testAddImageReturnsSelf(): void
    {
        $vehicle = new Vehicle();
        $image = new Image();

        self::assertSame(
            $vehicle,
            $vehicle->addImage($image)
        );
    }

    // vérifie le chaînage removeImage
    public function testRemoveImageReturnsSelf(): void
    {
        $vehicle = new Vehicle();
        $image = new Image();

        $vehicle->addImage($image);

        self::assertSame(
            $vehicle,
            $vehicle->removeImage($image)
        );
    }
}
