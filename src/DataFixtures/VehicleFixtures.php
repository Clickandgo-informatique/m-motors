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
    public const VEHICLES_BY_BRAND_MIN = 10;
    public const VEHICLES_BY_BRAND_MAX = 15;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $vehicleModels = $manager->getRepository(VehicleModel::class)->findAll();
        $suppliers = $manager->getRepository(Supplier::class)->findAll();
        $colors = $manager->getRepository(Color::class)->findAll();

        $modelsByBrand = [];

        foreach ($vehicleModels as $vehicleModel) {
            $modelsByBrand[$vehicleModel->getBrand()->getName()][] = $vehicleModel;
        }

        foreach ($modelsByBrand as $brandModels) {

            $count = random_int(
                self::VEHICLES_BY_BRAND_MIN,
                self::VEHICLES_BY_BRAND_MAX
            );

            for ($i = 0; $i < $count; $i++) {

                $vehicle = new Vehicle();

                $vehicleModel = $brandModels[array_rand($brandModels)];

                $vehicle->setVehicleModel($vehicleModel);
                $vehicle->setSupplier($suppliers[array_rand($suppliers)]);

                $vehicle->setStatus(
                    VehicleStatus::cases()[array_rand(VehicleStatus::cases())]
                );

                $vehicle->setUsageType(
                    VehicleUsageType::cases()[array_rand(VehicleUsageType::cases())]
                );

                $vehicle->setVin(strtoupper($faker->regexify('[A-HJ-NPR-Z0-9]{17}')));

                $vehicle->setGearType($vehicleModel->getGearType());
                $vehicle->setFuelType($vehicleModel->getFuelType());

                if (!empty($colors)) {
                    $vehicle->setColor($colors[array_rand($colors)]);
                }

                $vehicle->setPrice($faker->numberBetween(8000, 90000));
                $vehicle->setMileage($faker->numberBetween(0, 300000));

                $year = $faker->numberBetween(2005, (int) date('Y'));

                $vehicle->setFirstRegistrationDate(
                    \DateTimeImmutable::createFromFormat('Y-m-d', $year . '-01-01')
                );

                $vehicle->setIsFeatured(false);

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