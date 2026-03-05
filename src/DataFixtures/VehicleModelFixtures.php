<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\FuelType;
use App\Entity\VehicleModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class VehicleModelFixtures extends Fixture
{
    private array $brandCache = [];
    private array $modelCache = [];
    private array $variantCache = [];
    private array $fuelCache = [];

    public function load(ObjectManager $em): void
    {
        ini_set('memory_limit', '-1');

        $batchSize = 200;

        $projectDir = dirname(__DIR__, 2);
        $path = $projectDir . '/data/utac.csv';

        if (!file_exists($path)) {
            throw new \Exception("Fichier introuvable : $path");
        }

        $totalLines = $this->countLines($path) - 1;

        $output = new ConsoleOutput();
        $progressBar = new ProgressBar($output, $totalLines);
        $progressBar->start();

        $file = fopen($path, 'r');
        fgets($file); // skip header

        $i = 0;

        while (($rawLine = fgets($file)) !== false) {

            $row = str_getcsv($rawLine, ';');

            $row = array_map(
                fn($v) => $v !== null
                    ? trim(str_replace('""', '"', mb_convert_encoding($v, 'UTF-8', 'auto')), "\" \t\n\r")
                    : null,
                $row
            );

            $brandName   = $this->normalize($row[0] ?? null);
            $modelName   = $this->normalize($row[4] ?? null);
            $variantName = $this->normalize($row[3] ?? null);

            if (!$brandName || !$modelName) {
                continue;
            }

            $fuelName = $this->normalize($row[7] ?? null);

            $brand = $this->getBrand($em, $brandName);
            $model = $this->getModel($em, $brand, $modelName);
            $variant = $this->getVariant($em, $model, $variantName);
            $fuel = $fuelName ? $this->getFuel($em, $fuelName) : null;

            $vm = new VehicleModel();

            /*
            ==========================
            RELATIONS
            ==========================
            */          

            if ($variant) {
                $vm->setVariant($variant);
            }

            if ($fuel) {
                $vm->setFuelType($fuel);
            }

            /*
            ==========================
            DONNEES TECHNIQUES
            ==========================
            */

            $vm->setPowerHp($this->sanitizeNumber($row[10] ?? null, 2000));
            $vm->setPowerFiscal($this->sanitizeNumber($row[9] ?? null, 100));
            $vm->setConsumption($this->sanitizeNumber($row[15] ?? null, 50));
            $vm->setCo2($this->sanitizeNumber($row[16] ?? null, 2000));

            /*
            ==========================
            IDENTIFIANTS UTAC
            ==========================
            */

            $vm->setCnit($row[5] ?? null);
            $vm->setUtacCode($row[6] ?? null);

            /*
            ==========================
            DATE HOMOLOGATION
            ==========================
            */

            $dateString = $row[25] ?? null;

            $vm->setHomologationDate(
                $dateString && strtotime($dateString)
                    ? new \DateTime($dateString)
                    : null
            );

            $em->persist($vm);

            if (($i % $batchSize) === 0 && $i !== 0) {
                $em->flush();
                $em->clear();
                $this->resetLocalReferences();
            }

            $i++;
            $progressBar->advance();
        }

        fclose($file);

        $em->flush();
        $em->clear();

        $progressBar->finish();
        $output->writeln("\nImport terminé !");
    }

    private function normalize(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');

        $replacements = [
            'Hdi' => 'HDI',
            'Gti' => 'GTI',
            'Tdi' => 'TDI',
            'Dci' => 'DCI',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }

    private function sanitizeNumber($value, $max)
    {
        if (!is_numeric($value)) return null;

        $num = (float)$value;

        return $num > $max ? null : $num;
    }

    private function resetLocalReferences(): void
    {
        $this->brandCache = [];
        $this->modelCache = [];
        $this->variantCache = [];
        $this->fuelCache = [];
    }

    private function countLines(string $path): int
    {
        $lines = 0;
        $file = fopen($path, 'r');

        while (!feof($file)) {
            fgets($file);
            $lines++;
        }

        fclose($file);

        return $lines;
    }

    private function getBrand(ObjectManager $em, string $name): Brand
    {
        if (isset($this->brandCache[$name])) {
            return $this->brandCache[$name];
        }

        $brand = $em->getRepository(Brand::class)
            ->findOneBy(['name' => $name])
            ?? (new Brand())->setName($name);

        $em->persist($brand);

        return $this->brandCache[$name] = $brand;
    }

    private function getModel(ObjectManager $em, Brand $brand, string $name): Model
    {
        $key = $brand->getName() . '|' . $name;

        if (isset($this->modelCache[$key])) {
            return $this->modelCache[$key];
        }

        $model = $em->getRepository(Model::class)
            ->findOneBy(['name' => $name, 'brand' => $brand])
            ?? (new Model())->setName($name)->setBrand($brand);

        $em->persist($model);

        return $this->modelCache[$key] = $model;
    }

    private function getVariant(ObjectManager $em, Model $model, ?string $name): ?Variant
    {
        if (!$name) {
            return null;
        }

        $key = $model->getName() . '|' . $name;

        if (isset($this->variantCache[$key])) {
            return $this->variantCache[$key];
        }

        $variant = $em->getRepository(Variant::class)
            ->findOneBy(['name' => $name, 'model' => $model])
            ?? (new Variant())->setName($name)->setModel($model);

        $em->persist($variant);

        return $this->variantCache[$key] = $variant;
    }

    private function getFuel(ObjectManager $em, string $name): FuelType
    {
        if (isset($this->fuelCache[$name])) {
            return $this->fuelCache[$name];
        }

        $fuel = $em->getRepository(FuelType::class)
            ->findOneBy(['name' => $name])
            ?? (new FuelType())->setName($name);

        $em->persist($fuel);

        return $this->fuelCache[$name] = $fuel;
    }
}
