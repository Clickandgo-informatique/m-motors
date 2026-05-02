<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\FinancingType;
use App\Service\CustomerCodeGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Création de dossiers réalistes.
 *
 * Objectif :
 * - suppression des références fragiles
 * - sélection aléatoire dans la base réelle
 * - compatibilité avec import UTAC
 */
class DossierFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private CustomerCodeGenerator $codeGenerator
    ) {}

    public function load(ObjectManager $manager): void
    {
        $customers = $manager->getRepository(Customer::class)->findAll();
        $vehicles  = $manager->getRepository(Vehicle::class)->findAll();

        if (!$customers || !$vehicles) {
            throw new \RuntimeException('Customers ou Vehicles manquants.');
        }

        for ($i = 0; $i < 30; $i++) {

            /*
             * Sélection aléatoire stable
             */
            $customer = $customers[array_rand($customers)];
            $vehicle  = $vehicles[array_rand($vehicles)];

            /*
             * Type de financement
             */
            $financingType = FinancingType::cases()[array_rand(FinancingType::cases())];

            $type = in_array(
                $financingType,
                [FinancingType::LOA, FinancingType::LLD],
                true
            ) ? DossierType::RENTAL : DossierType::SALE;

            /*
             * Création dossier
             */
            $dossier = new Dossier();

            $dossier->setCustomer($customer);
            $dossier->setVehicle($vehicle);
            $dossier->setType($type);
            $dossier->setStatus('draft');

            $dossier->setDossierCode(
                $this->codeGenerator->generateDossierCode($customer)
            );

            $dossier->setFinancingType($financingType->value);

            $manager->persist($dossier);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class,
            VehicleFixtures::class,
        ];
    }
}
