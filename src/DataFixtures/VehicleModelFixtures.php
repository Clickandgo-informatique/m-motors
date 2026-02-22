<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\FuelType;
use App\Entity\Gear;
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
    private array $gearCache = [];

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

            // Nettoyage UTF‑8
            $row = array_map(
                fn($v) => $v !== null
                    ? trim(str_replace('""', '"', mb_convert_encoding($v, 'UTF-8', 'auto')), "\" \t\n\r")
                    : null,
                $row
            );

            /* ---------------------------------------------------------
               MAPPING UTAC (corrigé)
               ---------------------------------------------------------
               0 = lib_mrq_doss (marque)
               1 = lib_mod_doss (modèle dossier)
               2 = mrq_utac (IGNORÉ)
               3 = mod_utac (variante UTAC)
               4 = dscom (désignation commerciale → modèle affiché)
               5 = cnit
               6 = tvv
               7 = energ
               12 = typ_boite_nb_rapp
               22 = masse_ordma_min
               23 = masse_ordma_max
               24 = champ_v9
               25 = date_maj
            ---------------------------------------------------------- */

            $brandName   = $row[0];  // lib_mrq_doss
            $modelName   = $row[4];  // dscom (modèle affiché)
            $variantName = $row[3];  // mod_utac

            $fuelName    = $row[7];
            $gearName    = $row[12];

            $powerFiscal = $this->sanitizeNumber($row[9], 100);
            $powerHp     = $this->sanitizeNumber($row[10], 2000);

            $consumption = $this->sanitizeNumber($row[15], 50);
            $co2         = $this->sanitizeNumber($row[16], 2000);

            $massMin     = $this->sanitizeNumber($row[22], 100000);
            $massMax     = $this->sanitizeNumber($row[23], 100000);

            /* ---------------------------------------------------------
               ENTITÉS LIÉES
            ---------------------------------------------------------- */
            $brand   = $this->getBrand($em, $brandName);
            $model   = $this->getModel($em, $brand, $modelName);
            $variant = $this->getVariant($em, $model, $variantName);
            $fuel    = $this->getFuel($em, $fuelName);
            $gear    = $this->getGear($em, $gearName);

            /* ---------------------------------------------------------
               VehicleModel
            ---------------------------------------------------------- */
            $vm = new VehicleModel();
            $vm->setBrand($brand);
            $vm->setModel($model);
            $vm->setVariant($variant);
            $vm->setFuelType($fuel);
            $vm->setGear($gear);

            $vm->setPowerHp($powerHp);
            $vm->setPowerFiscal($powerFiscal);
            $vm->setConsumption($consumption);
            $vm->setCo2($co2);
            $vm->setMassMin($massMin);
            $vm->setMassMax($massMax);

            $vm->setCnit($row[5]);
            $vm->setUtacCode($row[6]);
            $vm->setEuroNorm($row[24]);

            $dateString = $row[25] ?? null;
            $vm->setHomologationDate(
                $dateString && strtotime($dateString)
                    ? new \DateTime($dateString)
                    : null
            );

            $em->persist($vm);

            if (($i % $batchSize) === 0) {
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

    private function sanitizeNumber($value, $max)
    {
        if (!is_numeric($value)) return null;
        $num = (float)$value;
        return $num > $max ? null : $num;
    }

    private function resetLocalReferences(): void
    {
        foreach ($this->brandCache as &$b) $b = null;
        foreach ($this->modelCache as &$m) $m = null;
        foreach ($this->variantCache as &$v) $v = null;
        foreach ($this->fuelCache as &$f) $f = null;
        foreach ($this->gearCache as &$g) $g = null;
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

    /* ---------------------------------------------------------
       CACHE ENTITÉS
    ---------------------------------------------------------- */

    private function getBrand(ObjectManager $em, string $name): Brand
    {
        if (isset($this->brandCache[$name])) return $this->brandCache[$name];

        $brand = $em->getRepository(Brand::class)->findOneBy(['name' => $name])
            ?? (new Brand())->setName($name);

        $em->persist($brand);
        return $this->brandCache[$name] = $brand;
    }

    private function getModel(ObjectManager $em, Brand $brand, string $name): Model
    {
        $key = $brand->getName() . '|' . $name;

        if (isset($this->modelCache[$key])) return $this->modelCache[$key];

        $model = $em->getRepository(Model::class)->findOneBy(['name' => $name, 'brand' => $brand])
            ?? (new Model())->setName($name)->setBrand($brand);

        $em->persist($model);
        return $this->modelCache[$key] = $model;
    }

    private function getVariant(ObjectManager $em, Model $model, string $name): Variant
    {
        $key = $model->getName() . '|' . $name;

        if (isset($this->variantCache[$key])) return $this->variantCache[$key];

        $variant = $em->getRepository(Variant::class)->findOneBy(['name' => $name, 'model' => $model])
            ?? (new Variant())->setName($name)->setModel($model);

        $em->persist($variant);
        return $this->variantCache[$key] = $variant;
    }

    private function getFuel(ObjectManager $em, string $name): FuelType
    {
        if (isset($this->fuelCache[$name])) return $this->fuelCache[$name];

        $fuel = $em->getRepository(FuelType::class)->findOneBy(['name' => $name])
            ?? (new FuelType())->setName($name);

        $em->persist($fuel);
        return $this->fuelCache[$name] = $fuel;
    }

    private function getGear(ObjectManager $em, string $type): Gear
    {
        if (isset($this->gearCache[$type])) return $this->gearCache[$type];

        $gear = $em->getRepository(Gear::class)->findOneBy(['type' => $type])
            ?? (new Gear())->setType($type);

        $em->persist($gear);
        return $this->gearCache[$type] = $gear;
    }
}
