<?php

namespace App\DataFixtures;

use App\Entity\Supplier;
use App\Entity\Brand;
use App\Enum\SupplierType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SupplierFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $referenceIndex = 0;

        // Tableau pour stocker les noms déjà utilisés
        $usedNames = [];

        /*
         * =====================================================
         * 1️⃣ CONSTRUCTEURS BASÉS SUR LES BRAND EXISTANTS
         * =====================================================
         */

        $brands = $manager->getRepository(Brand::class)->findAll();

        foreach ($brands as $brand) {

            $name = $brand->getName();

            // Sécurité supplémentaire au cas où
            if (in_array($name, $usedNames)) {
                continue;
            }

            $supplier = new Supplier();
            $supplier->setName($name);
            $supplier->setEmail(strtolower(str_replace(' ', '', $name)) . '@pro.auto');
            $supplier->setPhone($faker->phoneNumber());
            $supplier->setAddress($faker->streetAddress());
            $supplier->setCity($faker->city());
            $supplier->setPostalCode($faker->postcode());
            $supplier->setCountry('France');
            $supplier->setSiret($faker->numerify('##############'));
            $supplier->setType(SupplierType::MANUFACTURER);
            $supplier->setAverageDeliveryDelay($faker->numberBetween(5, 30));
            $supplier->setRating($faker->randomFloat(1, 3.5, 5));

            $manager->persist($supplier);

            $usedNames[] = $name;
            $this->addReference('supplier_' . $referenceIndex, $supplier);
            $referenceIndex++;
        }

        /*
         * =====================================================
         * 2️⃣ GROSSISTES / CENTRALES D'ACHAT
         * =====================================================
         */

        $wholesalers = [
            'Stellantis Distribution',
            'BCA Auto Enchères',
            'Manheim France',
            'AramisPro',
            'Groupe Emil Frey'
        ];

        foreach ($wholesalers as $name) {

            if (in_array($name, $usedNames)) {
                continue;
            }

            $supplier = new Supplier();
            $supplier->setName($name);
            $supplier->setEmail($faker->unique()->companyEmail());
            $supplier->setPhone($faker->phoneNumber());
            $supplier->setAddress($faker->streetAddress());
            $supplier->setCity($faker->city());
            $supplier->setPostalCode($faker->postcode());
            $supplier->setCountry('France');
            $supplier->setSiret($faker->numerify('##############'));
            $supplier->setType(SupplierType::WHOLESALER);
            $supplier->setAverageDeliveryDelay($faker->numberBetween(2, 15));
            $supplier->setRating($faker->randomFloat(1, 3, 5));

            $manager->persist($supplier);

            $usedNames[] = $name;
            $this->addReference('supplier_' . $referenceIndex, $supplier);
            $referenceIndex++;
        }

        /*
         * =====================================================
         * 3️⃣ MARCHANDS VO ALÉATOIRES
         * =====================================================
         */

        for ($i = 0; $i < 15; $i++) {

            // Génération unique sécurisée
            do {
                $name = $faker->company() . ' Auto';
            } while (in_array($name, $usedNames));

            $supplier = new Supplier();
            $supplier->setName($name);
            $supplier->setEmail($faker->unique()->companyEmail());
            $supplier->setPhone($faker->phoneNumber());
            $supplier->setAddress($faker->streetAddress());
            $supplier->setCity($faker->city());
            $supplier->setPostalCode($faker->postcode());
            $supplier->setCountry('France');
            $supplier->setSiret($faker->numerify('##############'));
            $supplier->setType(SupplierType::BROKER);
            $supplier->setAverageDeliveryDelay($faker->numberBetween(1, 10));
            $supplier->setRating($faker->randomFloat(1, 2.5, 5));

            $manager->persist($supplier);

            $usedNames[] = $name;
            $this->addReference('supplier_' . $referenceIndex, $supplier);
            $referenceIndex++;
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VehicleModelFixtures::class,
        ];
    }
}
