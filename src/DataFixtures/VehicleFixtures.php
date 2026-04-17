<?php

namespace App\DataFixtures;

use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
use App\Repository\SupplierRepository;
use App\Repository\VehicleModelRepository;
use App\Repository\ColorRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private VehicleModelRepository $vehicleModelRepository,
        private SupplierRepository $supplierRepository,
        private ColorRepository $colorRepository
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $vehicleModels = $this->vehicleModelRepository->findAll();
        $suppliers     = $this->supplierRepository->findAll();
        $colors        = $this->colorRepository->findAll();

        if (empty($vehicleModels) || empty($suppliers)) {
            throw new \RuntimeException('Données de base manquantes.');
        }

        // 👉 IMPORTANT : on centralise les véhicules dans un pool utilisable sans références
        $vehiclesPool = [];

        for ($i = 1; $i <= 50; $i++) {

            $vehicle = new Vehicle();

            $vehicle->setVin(strtoupper($faker->regexify('[A-HJ-NPR-Z0-9]{17}')));
            $vehicle->setRegistrationNumber(sprintf('MM-%03d-%s', $i, strtoupper($faker->lexify('??'))));
            $vehicle->setMileage($faker->numberBetween(0, 200000));
            $vehicle->setPrice($faker->numberBetween(5000, 60000));
            $vehicle->setStatus(VehicleStatus::AVAILABLE);
            $vehicle->setFirstRegistrationDate($faker->dateTimeThisDecade());

            if (!empty($colors)) {
                $vehicle->setColor($colors[array_rand($colors)]);
            }

            $vehicle->setVehicleModel($vehicleModels[array_rand($vehicleModels)]);
            $vehicle->setSupplier($suppliers[array_rand($suppliers)]);

            $manager->persist($vehicle);

            $this->addReference('vehicle_' . $i, $vehicle);

            // pool interne (optionnel mais utile debug)
            $vehiclesPool[] = $vehicle;
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