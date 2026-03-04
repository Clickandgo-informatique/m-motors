<?php

namespace App\DataFixtures;

use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
use App\Repository\SupplierRepository;
use App\Repository\VehicleModelRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private VehicleModelRepository $vehicleModelRepository,
        private SupplierRepository $supplierRepository
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $vehicleModels = $this->vehicleModelRepository->findAll();
        $suppliers     = $this->supplierRepository->findAll();

        if (empty($vehicleModels)) {
            throw new \RuntimeException('Aucun VehicleModel trouvé en base.');
        }

        if (empty($suppliers)) {
            throw new \RuntimeException('Aucun Supplier trouvé en base.');
        }

        for ($i = 0; $i < 50; $i++) {

            $vehicle = new Vehicle();

            $vehicle->setVin('VIN' . str_pad((string)$i, 14, '0', STR_PAD_LEFT));
            $vehicle->setRegistrationNumber(
                $faker->regexify('[A-Z]{2}-[0-9]{3}-[A-Z]{2}')
            );
            $vehicle->setMileage($faker->numberBetween(0, 200000));
            $vehicle->setYear($faker->numberBetween(2005, 2024));
            $vehicle->setPrice($faker->randomFloat(2, 5000, 60000));
            $vehicle->setStatus(VehicleStatus::AVAILABLE);

            // VehicleModel aléatoire depuis la base
            $vehicle->setVehicleModel(
                $vehicleModels[array_rand($vehicleModels)]
            );

            // Supplier aléatoire depuis la base
            $vehicle->setSupplier(
                $suppliers[array_rand($suppliers)]
            );

            $manager->persist($vehicle);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VehicleModelFixtures::class,
            SupplierFixtures::class,
        ];
    }
}
