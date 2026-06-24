<?php

namespace App\DataFixtures;

use App\Enum\VehicleStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FeaturedVehicleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $vehicles = $manager->getRepository(\App\Entity\Vehicle::class)->findAll();

        shuffle($vehicles);

        $featured = array_slice($vehicles, 0, 10);

        foreach ($featured as $index => $vehicle) {

            $vehicle->setIsFeatured(true);

            if ($index < 5) {
                $vehicle->setStatus(VehicleStatus::AVAILABLE_FOR_RENT);
            } else {
                $vehicle->setStatus(VehicleStatus::AVAILABLE_FOR_SALE);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VehicleFixtures::class,
        ];
    }
}
