<?php

namespace App\DataFixtures;

use App\DataFixtures\VehicleModelFixtures;
use App\Entity\Color;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use App\Enum\VehicleStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $vehicleModels = $manager->getRepository(VehicleModel::class)->findAll();
        $suppliers     = $manager->getRepository(Supplier::class)->findAll();
        $colors        = $manager->getRepository(Color::class)->findAll();

        if (!$vehicleModels || !$suppliers) {
            throw new \RuntimeException('Données de base manquantes.');
        }

        for ($i = 1; $i <= 1000; $i++) {

            $vehicle = new Vehicle();

            /*
            |--------------------------------------------------------------------------
            | STATUS (cohérent métier)
            |--------------------------------------------------------------------------
            */
            $status = $faker->randomElement([
                VehicleStatus::AVAILABLE_FOR_SALE,
                VehicleStatus::AVAILABLE_FOR_RENT,
                VehicleStatus::RESERVED,
                VehicleStatus::MAINTENANCE,
                VehicleStatus::SOLD,
            ]);

            $vehicle->setStatus($status);

            /*
            |--------------------------------------------------------------------------
            | VIN
            |--------------------------------------------------------------------------
            */
            $vehicle->setVin(strtoupper($faker->regexify('[A-HJ-NPR-Z0-9]{17}')));

            /*
            |--------------------------------------------------------------------------
            | NEUF / OCCASION
            |--------------------------------------------------------------------------
            */
            $isNew = $faker->boolean(35);

            if ($isNew) {
                $vehicle->setMileage(0);
                $vehicle->setFirstRegistrationDate(null);
                $vehicle->setStatus(VehicleStatus::AVAILABLE_FOR_SALE);
            } else {
                $vehicle->setMileage($faker->numberBetween(500, 220000));
                $vehicle->setFirstRegistrationDate($faker->dateTimeBetween('-10 years', 'now'));
            }

            /*
            |--------------------------------------------------------------------------
            | PRIX
            |--------------------------------------------------------------------------
            */
            $vehicle->setPrice($faker->numberBetween(8000, 90000));

            /*
            |--------------------------------------------------------------------------
            | RELATIONS
            |--------------------------------------------------------------------------
            */
            $vehicle->setVehicleModel($vehicleModels[array_rand($vehicleModels)]);
            $vehicle->setSupplier($suppliers[array_rand($suppliers)]);

            if (!empty($colors)) {
                $vehicle->setColor($colors[array_rand($colors)]);
            }          

            /*
            |--------------------------------------------------------------------------
            | PERSIST
            |--------------------------------------------------------------------------
            */
            $manager->persist($vehicle);

            $this->addReference('vehicle_' . $i, $vehicle);

            /*
            |--------------------------------------------------------------------------
            | BATCH OPTIMISÉ
            |--------------------------------------------------------------------------
            */
            if ($i % 100 === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VehicleModelFixtures::class,
            SupplierFixtures::class,
            ColorFixtures::class
        ];
    }
}
