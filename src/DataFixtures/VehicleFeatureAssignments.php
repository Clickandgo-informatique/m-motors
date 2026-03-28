<?php

namespace App\DataFixtures;

use App\Entity\Vehicle;
use App\Entity\Feature;
use App\Entity\FeatureCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class VehicleFeatureAssignments extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{

    public function load(ObjectManager $manager): void
    {
        $vehicleRepo = $manager->getRepository(Vehicle::class);
        $featureRepo = $manager->getRepository(Feature::class);
        $categoryRepo = $manager->getRepository(FeatureCategory::class);

        $vehicles = $vehicleRepo->findAll();
        $categories = $categoryRepo->findAll();

        // Regroupement des features par catégorie
        $featuresByCategory = [];
        foreach ($categories as $category) {
            $featuresByCategory[$category->getCode()] = $featureRepo->findBy(['category' => $category]);
        }

        foreach ($vehicles as $vehicle) {
            foreach ($featuresByCategory as $categoryCode => $features) {
                shuffle($features);

                // Définir la probabilité que la catégorie soit présente selon le type de véhicule
                $chance = match ($categoryCode) {
                    'comfort', 'safety', 'multimedia' => 1.0,
                    'driver_assistance', 'performance', 'lighting_visibility' => 0.7,
                    'eco', 'practicality', 'exterior', 'interior' => 0.5,
                    default => 0.5
                };

                if (mt_rand() / mt_getrandmax() <= $chance) {
                    // Toujours au moins une feature
                    $vehicle->addFeature($features[0]);

                    // 0 à 2 features supplémentaires aléatoires
                    $additionalCount = rand(0, min(2, count($features) - 1));
                    for ($i = 1; $i <= $additionalCount; $i++) {
                        $vehicle->addFeature($features[$i]);
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

    // Déclaration des dépendances pour s'assurer que les catégories et features existent
    public function getDependencies(): array
    {
        return [
            VehicleFixtures::class,
            FeatureFixtures::class,
        ];
    }
}
