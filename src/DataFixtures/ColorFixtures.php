<?php

namespace App\DataFixtures;

use App\Entity\Color;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ColorFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $colors = [
            'Jaune métallisé',
            'Tilleul métallisé',
            'Noir satiné',
            'Bleu mat',
            'Rouge nacré',
            'Gris métallisé',
            'Blanc perlé',
            'Vert satiné',
            'Bronze métallisé',
            'Noir mat',
            'Argent métallisé',
            'Bleu nuit nacré',
            'Orange satiné',
            'Beige mat',
            'Rouge métallisé',
        ];

        foreach ($colors as $name) {
            $color = new Color();
            $color->setName($name);
            $manager->persist($color);
        }

        $manager->flush();
    }
}
