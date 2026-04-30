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

            // =========================================================
            // STATUS
            // =========================================================
            $vehicle->setStatus(VehicleStatus::AVAILABLE, true);

            // =========================================================
            // VIN
            // =========================================================
            $vehicle->setVin(strtoupper($faker->regexify('[A-HJ-NPR-Z0-9]{17}')));

            // =========================================================
            // NEUF / OCCASION
            // =========================================================
            $isNew = $faker->boolean(35);

            if ($isNew) {
                $vehicle->setRegistrationNumber(null);
                $vehicle->setMileage(0);
                $vehicle->setFirstRegistrationDate(null);
            } else {
                $vehicle->setRegistrationNumber(
                    sprintf('AA-%03d-%s', $i, strtoupper($faker->lexify('??')))
                );
                $vehicle->setMileage($faker->numberBetween(500, 220000));
                $vehicle->setFirstRegistrationDate($faker->dateTimeBetween('-10 years', 'now'));
            }

            // =========================================================
            // PRICE
            // =========================================================
            $vehicle->setPrice($faker->numberBetween(8000, 90000));

            // =========================================================
            // RELATIONS
            // =========================================================
            $vehicle->setVehicleModel($vehicleModels[array_rand($vehicleModels)]);
            $vehicle->setSupplier($suppliers[array_rand($suppliers)]);

            if (!empty($colors)) {
                $vehicle->setColor($colors[array_rand($colors)]);
            }          

            $manager->persist($vehicle);

            // =========================================================
            // ⭐ IMPORTANT : REFERENCES FIXTURES
            // =========================================================
            $this->addReference('vehicle_' . $i, $vehicle);

            // =========================================================
            // BATCH
            // =========================================================
            if ($i % 100 === 0) {
                $manager->flush();
                $manager->clear();

                $vehicleModels = $manager->getRepository(VehicleModel::class)->findAll();
                $suppliers     = $manager->getRepository(Supplier::class)->findAll();
                $colors        = $manager->getRepository(Color::class)->findAll();
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
