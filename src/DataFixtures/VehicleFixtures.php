<?php

namespace App\DataFixtures;

use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use App\Entity\Color;
use App\Enum\VehicleStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VehicleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em): void
    {
        $models = $em->getRepository(VehicleModel::class)->findAll();
        $colors = $em->getRepository(Color::class)->findAll();

        if (!$models) {
            throw new \Exception("Aucun VehicleModel trouvé.");
        }

        if (!$colors) {
            throw new \Exception("Aucune couleur trouvée. Charge d'abord ColorFixtures.");
        }

        $batchSize = 100;

        for ($i = 1; $i <= 100; $i++) {

            $vehicle = new Vehicle();

            $vehicle->setModel($models[array_rand($models)]);
            $vehicle->setColor($colors[array_rand($colors)]);
            $vehicle->setRegistrationNumber($this->generateRegistrationNumber());


            $vehicle->setMileage(rand(0, 200000));
            $vehicle->setYear(rand(2005, 2024));
            $vehicle->setPrice(rand(5000, 60000));
            $vehicle->setVin($this->generateVin());

            $vehicle->setStatus($this->randomStatus());

            $em->persist($vehicle);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();

                // ⚠️ Recharger après clear()
                $models = $em->getRepository(VehicleModel::class)->findAll();
                $colors = $em->getRepository(Color::class)->findAll();
            }
        }

        $em->flush();
    }
    private function generateRegistrationNumber(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $firstPart  = $letters[random_int(0, 25)] . $letters[random_int(0, 25)];
        $numbers    = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $secondPart = $letters[random_int(0, 25)] . $letters[random_int(0, 25)];

        return sprintf('%s-%s-%s', $firstPart, $numbers, $secondPart);
    }
    private function generateVin(): string
    {
        $chars = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789'; // sans I,O,Q

        $vin = '';
        for ($i = 0; $i < 17; $i++) {
            $vin .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $vin;
    }
    private function randomStatus(): VehicleStatus
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 60 => VehicleStatus::AVAILABLE,
            $rand <= 75 => VehicleStatus::RENTED,
            $rand <= 90 => VehicleStatus::SOLD,
            default => VehicleStatus::MAINTENANCE,
        };
    }

    public function getDependencies(): array
    {
        return [
            VehicleModelFixtures::class,
            ColorFixtures::class,
        ];
    }
}
