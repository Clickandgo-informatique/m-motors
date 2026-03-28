<?php

namespace App\DataFixtures;

use App\Entity\Feature;
use App\Entity\FeatureCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class FeatureFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['FeatureFixtures'];
    }
    public function load(ObjectManager $manager): void
    {

        //Création des catégories d'options des véhicules
        $json = file_get_contents(__DIR__ . '/../../data/vehicle_features.json');

        $data = json_decode($json, true);

        $categories = $data['categories'];
        $features = $data['features'];
        $categoryMap = [];

        foreach ($categories as $categoryData) {
            $newCategory = new FeatureCategory();

            $newCategory->setCode($categoryData['code']);
            $newCategory->setLabel($categoryData['label']);

            $manager->persist($newCategory);
            $categoryMap[$categoryData['code']] = $newCategory;
        }
        $manager->flush();

        //Création de la liste d'options
        foreach ($features as $featureData) {
            $feature = new Feature();
            $feature->setCode($featureData['code']);
            $feature->setLabel($featureData['label']);

            $categoryCode = $featureData['category'];
            if (isset($categoryMap[$categoryCode])) {
                $feature->setCategory($categoryMap[$categoryCode]);
            }

            $manager->persist($feature);
        }
        $manager->flush();
    }
}
