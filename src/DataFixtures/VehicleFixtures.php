<?php

namespace App\DataFixtures;

use App\Entity\Color;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use App\Enum\VehicleStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Génération des véhicules.
 *
 * Objectif :
 * - dataset stable
 * - sans références Doctrine
 * - compatible clear + batch
 */
class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    private const MIN_PER_STATUS = 100;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $vehicleModels = $manager->getRepository(VehicleModel::class)->findAll();
        $suppliers     = $manager->getRepository(Supplier::class)->findAll();
        $colors        = $manager->getRepository(Color::class)->findAll();

        $statuses = [
            VehicleStatus::AVAILABLE_FOR_SALE,
            VehicleStatus::AVAILABLE_FOR_RENT,
            VehicleStatus::RESERVED,
            VehicleStatus::MAINTENANCE,
            VehicleStatus::SOLD,
        ];

        foreach ($statuses as $status) {

            for ($i = 0; $i < self::MIN_PER_STATUS; $i++) {

                $vehicle = new Vehicle();

                $vehicle->setStatus($status);
                $vehicle->setVin(strtoupper($faker->regexify('[A-HJ-NPR-Z0-9]{17}')));

                $vehicle->setVehicleModel($vehicleModels[array_rand($vehicleModels)]);
                $vehicle->setSupplier($suppliers[array_rand($suppliers)]);

                if ($colors) {
                    $vehicle->setColor($colors[array_rand($colors)]);
                }

                $vehicle->setPrice($faker->numberBetween(8000, 90000));

                $manager->persist($vehicle);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VehicleModelFixtures::class,
            SupplierFixtures::class,
            ColorFixtures::class,
        ];
    }
}
