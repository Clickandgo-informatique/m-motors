<?php

namespace App\DataFixtures;

use App\Entity\Color;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use App\Enum\VehicleStatus;
use App\Enum\VehicleUsageType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    private const MIN_PER_STATUS = 100;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $vehicleModels = $manager->getRepository(VehicleModel::class)->findAll();
        $suppliers = $manager->getRepository(Supplier::class)->findAll();
        $colors = $manager->getRepository(Color::class)->findAll();

        $statuses = [
            VehicleStatus::AVAILABLE_FOR_SALE,
            VehicleStatus::AVAILABLE_FOR_RENT,
            VehicleStatus::RENTED,
            VehicleStatus::MAINTENANCE,
            VehicleStatus::ORDERED,
        ];

        $usageTypes = [
            VehicleUsageType::SALE,
            VehicleUsageType::RENT,
            VehicleUsageType::BOTH,
        ];

        foreach ($statuses as $status) {
            for ($i = 0; $i < self::MIN_PER_STATUS; $i++) {

                $vehicle = new Vehicle();

                // Status physique
                $vehicle->setStatus($status);

                // Type d’usage commercial
                $vehicle->setUsageType($usageTypes[array_rand($usageTypes)]);

                // Identifiants
                $vehicle->setVin(strtoupper($faker->regexify('[A-HJ-NPR-Z0-9]{17}')));


                // Relations
                $vehicle->setVehicleModel($vehicleModels[array_rand($vehicleModels)]);
                $vehicle->setSupplier($suppliers[array_rand($suppliers)]);

                //Boite de vitesse (dépend de vehicleModel)
                $vehicle->setGearType($vehicle->getVehicleModel()->getGearType());

                //Carburant (dépend de vehicleModel)
                $vehicle->setFuelType($vehicle->getVehicleModel()->getFuelType());

                if (!empty($colors)) {
                    $vehicle->setColor($colors[array_rand($colors)]);
                }

                // Prix
                $vehicle->setPrice($faker->numberBetween(8000, 90000));

                // Kilométrage
                $vehicle->setMileage($faker->numberBetween(0, 300000));

                // Date de première immatriculation
                $year = $faker->numberBetween(2005, (int) date('Y'));
                $vehicle->setFirstRegistrationDate(
                    \DateTimeImmutable::createFromFormat('Y-m-d', $year . '-01-01')
                );

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
