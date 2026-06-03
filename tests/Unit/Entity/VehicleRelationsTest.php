<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Color;
use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Favorite;
use App\Entity\Maintenance;
use App\Entity\Rental;
use App\Entity\Sale;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

class VehicleRelationsTest extends TestCase
{
    // vérifie l'ajout d'un dossier
    public function testAddDossier(): void
    {
        $vehicle = new Vehicle();

        $dossier = (new Dossier())
            ->setCustomer(new Customer());

        $vehicle->addDossier($dossier);

        self::assertCount(
            1,
            $vehicle->getDossiers()
        );

        self::assertTrue(
            $vehicle->getDossiers()->contains($dossier)
        );

        self::assertSame(
            $vehicle,
            $dossier->getVehicle()
        );
    }

    // vérifie qu'un dossier ne peut être ajouté qu'une fois
    public function testAddDossierOnlyOnce(): void
    {
        $vehicle = new Vehicle();

        $dossier = (new Dossier())
            ->setCustomer(new Customer());

        $vehicle->addDossier($dossier);
        $vehicle->addDossier($dossier);

        self::assertCount(
            1,
            $vehicle->getDossiers()
        );
    }

    // vérifie la suppression d'un dossier
    public function testRemoveDossier(): void
    {
        $vehicle = new Vehicle();

        $dossier = (new Dossier())
            ->setCustomer(new Customer());

        $vehicle->addDossier($dossier);

        $vehicle->removeDossier($dossier);

        self::assertCount(
            0,
            $vehicle->getDossiers()
        );

        self::assertNull(
            $dossier->getVehicle()
        );
    }

    // vérifie l'ajout d'une maintenance
    public function testAddMaintenance(): void
    {
        $vehicle = new Vehicle();
        $maintenance = new Maintenance();

        $vehicle->addMaintenance($maintenance);

        self::assertCount(
            1,
            $vehicle->getMaintenances()
        );

        self::assertSame(
            $vehicle,
            $maintenance->getVehicle()
        );
    }

    // vérifie qu'une maintenance ne peut être ajoutée qu'une fois
    public function testAddMaintenanceOnlyOnce(): void
    {
        $vehicle = new Vehicle();
        $maintenance = new Maintenance();

        $vehicle->addMaintenance($maintenance);
        $vehicle->addMaintenance($maintenance);

        self::assertCount(
            1,
            $vehicle->getMaintenances()
        );
    }

    // vérifie la suppression d'une maintenance
    public function testRemoveMaintenance(): void
    {
        $vehicle = new Vehicle();
        $maintenance = new Maintenance();

        $vehicle->addMaintenance($maintenance);
        $vehicle->removeMaintenance($maintenance);

        self::assertCount(
            0,
            $vehicle->getMaintenances()
        );

        self::assertNull(
            $maintenance->getVehicle()
        );
    }

    // vérifie l'ajout d'une location
    public function testAddRental(): void
    {
        $vehicle = new Vehicle();
        $rental = new Rental();

        $vehicle->addRental($rental);

        self::assertCount(
            1,
            $vehicle->getRentals()
        );

        self::assertSame(
            $vehicle,
            $rental->getVehicle()
        );
    }

    // vérifie qu'une location ne peut être ajoutée qu'une fois
    public function testAddRentalOnlyOnce(): void
    {
        $vehicle = new Vehicle();
        $rental = new Rental();

        $vehicle->addRental($rental);
        $vehicle->addRental($rental);

        self::assertCount(
            1,
            $vehicle->getRentals()
        );
    }

    // vérifie la suppression d'une location
    public function testRemoveRental(): void
    {
        $vehicle = new Vehicle();
        $rental = new Rental();

        $vehicle->addRental($rental);
        $vehicle->removeRental($rental);

        self::assertCount(
            0,
            $vehicle->getRentals()
        );

        self::assertNull(
            $rental->getVehicle()
        );
    }

    // vérifie l'ajout d'une vente
    public function testAddSale(): void
    {
        $vehicle = new Vehicle();
        $sale = new Sale();

        $vehicle->addSale($sale);

        self::assertCount(
            1,
            $vehicle->getSales()
        );

        self::assertSame(
            $vehicle,
            $sale->getVehicle()
        );
    }

    // vérifie qu'une vente ne peut être ajoutée qu'une fois
    public function testAddSaleOnlyOnce(): void
    {
        $vehicle = new Vehicle();
        $sale = new Sale();

        $vehicle->addSale($sale);
        $vehicle->addSale($sale);

        self::assertCount(
            1,
            $vehicle->getSales()
        );
    }

    // vérifie la suppression d'une vente
    public function testRemoveSale(): void
    {
        $vehicle = new Vehicle();
        $sale = new Sale();

        $vehicle->addSale($sale);
        $vehicle->removeSale($sale);

        self::assertCount(
            0,
            $vehicle->getSales()
        );

        self::assertNull(
            $sale->getVehicle()
        );
    }

    // vérifie l'ajout d'un favori
    public function testAddFavorite(): void
    {
        $vehicle = new Vehicle();
        $favorite = new Favorite();

        $vehicle->addFavorite($favorite);

        self::assertCount(
            1,
            $vehicle->getFavorites()
        );

        self::assertTrue(
            $vehicle->getFavorites()->contains($favorite)
        );
    }

    // vérifie qu'un favori ne peut être ajouté qu'une fois
    public function testAddFavoriteOnlyOnce(): void
    {
        $vehicle = new Vehicle();
        $favorite = new Favorite();

        $vehicle->addFavorite($favorite);
        $vehicle->addFavorite($favorite);

        self::assertCount(
            1,
            $vehicle->getFavorites()
        );
    }

    // vérifie la suppression d'un favori
    public function testRemoveFavorite(): void
    {
        $vehicle = new Vehicle();
        $favorite = new Favorite();

        $favorite->setVehicle($vehicle);

        $vehicle->addFavorite($favorite);
        $vehicle->removeFavorite($favorite);

        self::assertCount(
            0,
            $vehicle->getFavorites()
        );

        self::assertNull(
            $favorite->getVehicle()
        );
    }
    // vérifie la relation couleur
    public function testColor(): void
    {
        $vehicle = new Vehicle();
        $color = new Color();

        $vehicle->setColor($color);

        self::assertSame(
            $color,
            $vehicle->getColor()
        );
    }

    // vérifie qu'une couleur peut être supprimée
    public function testColorCanBeNull(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setColor(null);

        self::assertNull(
            $vehicle->getColor()
        );
    }

    // vérifie la relation fournisseur
    public function testSupplier(): void
    {
        $vehicle = new Vehicle();
        $supplier = new Supplier();

        $vehicle->setSupplier($supplier);

        self::assertSame(
            $supplier,
            $vehicle->getSupplier()
        );
    }

    // vérifie qu'un fournisseur peut être supprimé
    public function testSupplierCanBeNull(): void
    {
        $vehicle = new Vehicle();

        $vehicle->setSupplier(null);

        self::assertNull(
            $vehicle->getSupplier()
        );
    }
}
