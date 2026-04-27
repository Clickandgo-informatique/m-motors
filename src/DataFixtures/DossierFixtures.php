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

class DossierFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private CustomerCodeGenerator $codeGenerator
    ) {}

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 30; $i++) {

            // =========================================================
            // RÉFÉRENCES STABLES
            // =========================================================
            $customer = $this->getReference(
                'customer_' . random_int(1, 50),
                Customer::class
            );

            $vehicle = $this->getReference(
                'vehicle_' . random_int(1, 50),
                Vehicle::class
            );

            // =========================================================
            // FINANCING TYPE
            // =========================================================
            $financingType = FinancingType::cases()[array_rand(FinancingType::cases())];

            $type = in_array(
                $financingType,
                [FinancingType::LOA, FinancingType::LLD],
                true
            ) ? DossierType::RENTAL : DossierType::SALE;

            // =========================================================
            // DOSSIER
            // =========================================================
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