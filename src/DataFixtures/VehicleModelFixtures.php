<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\FuelType;
use App\Entity\VehicleModel;
use App\Entity\BodyType;
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
    private array $cnitCache = [];

    private array $bodyTypes = [];

    public function load(ObjectManager $em): void
    {
        ini_set('memory_limit', '-1');

        $batchSize = 1000;

        $projectDir = dirname(__DIR__, 2);
        $path = $projectDir . '/data/utac.csv';

        if (!file_exists($path)) {
            throw new \Exception("CSV introuvable : $path");
        }

        $file = fopen($path, 'r');

        /*
        =========================
        HEADER MAPPING
        =========================
        */

        $header = fgetcsv($file, 0, ';');

        foreach ($header as &$col) {
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
        }

        $map = [];

        foreach ($header as $i => $col) {
            $map[strtolower(trim($col))] = $i;
        }

        /*
        =========================
        CHARGEMENT BODY TYPES
        =========================
        */

        $this->bodyTypes = $em
            ->getRepository(BodyType::class)
            ->findAll();

        $totalLines = $this->countLines($path) - 1;

        $output = new ConsoleOutput();
        $progressBar = new ProgressBar($output, $totalLines);
        $progressBar->start();

        $i = 0;

        while (($row = fgetcsv($file, 0, ';')) !== false) {

            $cnit = $this->sanitizeString(
                $this->col($row, $map, 'cnit'),
                50
            );

            if (!$cnit || isset($this->cnitCache[$cnit])) {
                continue;
            }

            $this->cnitCache[$cnit] = true;

            /*
            =========================
            DONNEES PRINCIPALES
            =========================
            */

            $brandName = $this->normalize(
                $this->col($row, $map, 'lib_mrq_doss')
            );

            $modelName = $this->normalize(
                $this->col($row, $map, 'dscom')
            );

            $variantName = $this->normalize(
                $this->col($row, $map, 'mod_utac')
            );

            if (!$brandName || !$modelName) {
                continue;
            }

            $fuelName = $this->normalizeFuel(
                $this->col($row, $map, 'energ')
            );

            /*
            =========================
            ENTITES RELATIONNELLES
            =========================
            */

            $brand = $this->getBrand($em, $brandName);
            $model = $this->getModel($em, $brand, $modelName);
            $variant = $this->getVariant($em, $model, $variantName);
            $fuel = $fuelName ? $this->getFuel($em, $fuelName) : null;

            /*
            =========================
            VEHICLE MODEL
            =========================
            */

            $vm = new VehicleModel();

            $vm->setBrand($brand);
            $vm->setModel($model);
            $vm->setBodyType($this->randomBodyType());

            if ($variant) {
                $vm->setVariant($variant);
            }

            if ($fuel) {
                $vm->setFuelType($fuel);
            }

            /*
            =========================
            PUISSANCES
            =========================
            */

            $vm->setPowerHp(
                $this->sanitizeNumber(
                    $this->col($row, $map, 'puiss_max'),
                    2000
                )
            );

            $vm->setPowerFiscal(
                $this->sanitizeNumber(
                    $this->col($row, $map, 'puiss_admin'),
                    100
                )
            );

            /*
            =========================
            CONSOMMATION
            =========================
            */

            $vm->setConsumption(
                $this->sanitizeNumber(
                    $this->col($row, $map, 'conso_mixte'),
                    50
                )
            );

            $vm->setCo2(
                $this->sanitizeNumber(
                    $this->col($row, $map, 'co2_mixte'),
                    2000
                )
            );

            /*
            =========================
            MASSES
            =========================
            */

            $massMin = $this->sanitizeNumber(
                $this->col($row, $map, 'masse_ordma_min'),
                10000
            );

            $massMax = $this->sanitizeNumber(
                $this->col($row, $map, 'masse_ordma_max'),
                10000
            );

            if ($massMin && $massMax && $massMin > $massMax) {
                $massMin = null;
            }

            $vm->setMassMin($massMin);
            $vm->setMassMax($massMax);

            /*
            =========================
            IDENTIFIANTS
            =========================
            */

            $vm->setCnit($cnit);

            $vm->setUtacCode(
                $this->sanitizeString(
                    $this->col($row, $map, 'tvv'),
                    50
                )
            );

            /*
            =========================
            DATE HOMOLOGATION
            =========================
            */

            $date = $this->col($row, $map, 'date_maj');

            if ($date && strtotime($date)) {
                $vm->setHomologationDate(new \DateTime($date));
            }

            $em->persist($vm);

            /*
            =========================
            BATCH FLUSH
            =========================
            */

            if (($i % $batchSize) === 0 && $i !== 0) {

                $em->flush();
                $em->clear();

                $this->resetCache();

                $this->bodyTypes = $em
                    ->getRepository(BodyType::class)
                    ->findAll();
            }

            $i++;
            $progressBar->advance();
        }

        fclose($file);

        $em->flush();
        $em->clear();

        $progressBar->finish();

        $output->writeln("\nImport terminé.");
    }

    private function randomBodyType(): ?BodyType
    {
        if (empty($this->bodyTypes)) {
            return null;
        }

        return $this->bodyTypes[array_rand($this->bodyTypes)];
    }

    private function col(array $row, array $map, string $name)
    {
        $i = $map[$name] ?? null;
        return $i !== null ? ($row[$i] ?? null) : null;
    }

    private function normalize(?string $value): ?string
    {
        if (!$value) return null;

        $value = mb_strtolower(trim($value));
        $value = mb_convert_case($value, MB_CASE_TITLE);

        return $value;
    }

    private function normalizeFuel(?string $fuel): ?string
    {
        if (!$fuel) return null;

        $fuel = strtolower(trim($fuel));

        $map = [
            'diesel' => 'Diesel',
            'gazole' => 'Diesel',
            'gasoil' => 'Diesel',
            'essence' => 'Essence',
            'hybride' => 'Hybride',
            'electric' => 'Electrique',
            'electrique' => 'Electrique'
        ];

        return $map[$fuel] ?? ucfirst($fuel);
    }

    private function sanitizeNumber($value, $max)
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);

        if (!is_numeric($value)) {
            return null;
        }

        $num = (float)$value;

        if ($num > $max) {
            return null;
        }

        return $num;
    }

    private function sanitizeString(?string $value, int $max): ?string
    {
        if (!$value) return null;

        $value = trim($value);

        return mb_strlen($value) > $max ? null : $value;
    }

    private function resetCache(): void
    {
        $this->brandCache = [];
        $this->modelCache = [];
        $this->variantCache = [];
        $this->fuelCache = [];
    }

    private function countLines($path): int
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
            ->findOneBy(['name' => $name]);

        if (!$brand) {
            $brand = (new Brand())->setName($name);
            $em->persist($brand);
        }

        $this->brandCache[$name] = $brand;

        return $brand;
    }

    private function getModel(ObjectManager $em, Brand $brand, string $name): Model
    {
        $key = $brand->getName() . '|' . $name;

        if (isset($this->modelCache[$key])) {
            return $this->modelCache[$key];
        }

        $model = $em->getRepository(Model::class)
            ->findOneBy([
                'name' => $name,
                'brand' => $brand
            ]);

        if (!$model) {
            $model = (new Model())
                ->setName($name)
                ->setBrand($brand);

            $em->persist($model);
        }

        $this->modelCache[$key] = $model;

        return $model;
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
            ->findOneBy([
                'name' => $name,
                'model' => $model
            ]);

        if (!$variant) {
            $variant = (new Variant())
                ->setName($name)
                ->setModel($model);

            $em->persist($variant);
        }

        $this->variantCache[$key] = $variant;

        return $variant;
    }

    private function getFuel(ObjectManager $em, string $name): FuelType
    {
        if (isset($this->fuelCache[$name])) {
            return $this->fuelCache[$name];
        }

        $fuel = $em->getRepository(FuelType::class)
            ->findOneBy(['name' => $name]);

        if (!$fuel) {
            $fuel = (new FuelType())
                ->setName($name);

            $em->persist($fuel);
        }

        $this->fuelCache[$name] = $fuel;

        return $fuel;
    }
}
