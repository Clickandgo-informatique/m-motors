<?php

namespace App\DataFixtures;

use App\Entity\BodyType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BodyTypeFixtures extends Fixture
{
    public function load(ObjectManager $em): void
    {
        $types = [
            ['SUV', 'suv', 'fa-car-side'],
            ['Berline', 'berline', 'fa-car'],
            ['Break', 'break', 'fa-car-bump'],
            ['Citadine', 'citadine', 'fa-car-alt'],
            ['Coupé', 'coupe', 'fa-car-sports'],
            ['Cabriolet', 'cabriolet', 'fa-car-convertible'],
            ['Monospace', 'monospace', 'fa-car-side'],
            ['Pick-up', 'pickup', 'fa-truck-pickup'],
            ['Utilitaire', 'utilitaire', 'fa-truck']
        ];

        $position = 1;

        foreach ($types as [$name, $slug, $icon]) {

            $bodyType = new BodyType();
            $bodyType->setName($name);
            $bodyType->setSlug($slug);
            $bodyType->setPosition($position++);
            $bodyType->setIcon($icon);

            $em->persist($bodyType);
        }

        $em->flush();
    }
}
