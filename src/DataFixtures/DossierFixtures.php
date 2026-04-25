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
        // =========================================================
        // CHECK REFERENCES (STRICT MODE DOCTRINE)
        // =========================================================
        if (
            !$this->hasReference('customer_1', Customer::class) ||
            !$this->hasReference('vehicle_1', Vehicle::class)
        ) {
            throw new \RuntimeException('Customers ou Vehicles non chargés correctement.');
        }

        // =========================================================
        // DOSSIERS
        // =========================================================
        for ($i = 0; $i < 30; $i++) {

            /** @var Customer $customer */
            $customer = $this->getReference(
                'customer_' . random_int(1, 50),
                Customer::class
            );

            /** @var Vehicle $vehicle */
            $vehicle = $this->getReference(
                'vehicle_' . random_int(1, 50),
                Vehicle::class
            );

            $financingType = FinancingType::cases()[array_rand(FinancingType::cases())];

            $type = in_array(
                $financingType,
                [FinancingType::LOA, FinancingType::LLD],
                true
            )
                ? DossierType::RENTAL
                : DossierType::SALE;

            $dossier = new Dossier();

            // =====================================================
            // RELATIONS
            // =====================================================
            $dossier->setCustomer($customer);
            $dossier->setVehicle($vehicle);
            $dossier->setType($type);

            // =====================================================
            // STATUS
            // =====================================================
            $dossier->setStatus('draft');

            // =====================================================
            // CODE
            // =====================================================
            $dossier->setDossierCode(
                $this->codeGenerator->generateDossierCode($customer)
            );

            // =====================================================
            // FINANCING
            // =====================================================
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
