<?php

namespace App\DataFixtures;

use App\Entity\Feature;
use App\Entity\FeatureCategory;
use App\Entity\VehicleModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VehicleFeatureAssignments extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $vehicleModelRepo = $manager->getRepository(VehicleModel::class);
        $featureRepo = $manager->getRepository(Feature::class);
        $categoryRepo = $manager->getRepository(FeatureCategory::class);

        $vehicleModels = $vehicleModelRepo->findAll();
        $categories = $categoryRepo->findAll();

        $featuresByCategory = [];

        foreach ($categories as $category) {
            $featuresByCategory[$category->getCode()] = $featureRepo->findBy([
                'category' => $category
            ]);
        }

        foreach ($vehicleModels as $vehicleModel) {
            foreach ($featuresByCategory as $categoryCode => $features) {
                if (empty($features)) {
                    continue;
                }

                shuffle($features);

                $chance = match ($categoryCode) {
                    'comfort', 'safety', 'multimedia' => 1.0,
                    'driver_assistance', 'performance', 'lighting_visibility' => 0.7,
                    'eco', 'practicality', 'exterior', 'interior' => 0.5,
                    default => 0.5,
                };

                if (mt_rand() / mt_getrandmax() <= $chance) {
                    $vehicleModel->addFeature($features[0]);

                    $additionalCount = rand(0, min(2, count($features) - 1));

                    for ($i = 1; $i <= $additionalCount; $i++) {
                        $vehicleModel->addFeature($features[$i]);
                    }
                }
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['VehicleFeatureAssignments'];
    }

    public function getDependencies(): array
    {
        return [
            VehicleFixtures::class,
            FeatureFixtures::class,
        ];
    }
}
