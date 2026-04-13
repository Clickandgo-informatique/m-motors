<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierStatus;
use App\Enum\DossierType;
use App\Enum\FinancingType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DossierFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $customers = [];
        $vehicles = [];

        // =========================
        // CUSTOMERS (safe range)
        // =========================
        for ($i = 1; $i <= 50; $i++) {
            if ($this->hasReference('customer_' . $i, Customer::class)) {
                $customers[] = $this->getReference('customer_' . $i, Customer::class);
            }
        }

        // =========================
        // VEHICLES (safe range)
        // =========================
        for ($i = 1; $i <= 200; $i++) {
            if ($this->hasReference('vehicle_' . $i, Vehicle::class)) {
                $vehicles[] = $this->getReference('vehicle_' . $i, Vehicle::class);
            }
        }

        if (empty($customers) || empty($vehicles)) {
            throw new \RuntimeException('Customers ou Vehicles non chargés correctement.');
        }

        // =========================
        // DOSSIERS
        // =========================
        for ($i = 0; $i < 30; $i++) {

            $customer = $customers[array_rand($customers)];
            $vehicle  = $vehicles[array_rand($vehicles)];

            $financingType = FinancingType::cases()[array_rand(FinancingType::cases())];

            $type = in_array($financingType, [FinancingType::LOA, FinancingType::LLD], true)
                ? DossierType::FINANCING
                : DossierType::PURCHASE;

            $dossier = new Dossier();

            $dossier
                ->setCustomer($customer)
                ->setVehicle($vehicle)
                ->setType($type)
                ->setStatus(DossierStatus::DRAFT);

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
