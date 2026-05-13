<?php

namespace App\DataFixtures;

use App\Entity\BodyType;
use App\Entity\Brand;
use App\Entity\FuelType;
use App\Entity\GearType;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\VehicleModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Import des VehicleModel depuis le fichier UTAC CSV.
 *
 * Objectifs :
 * - construire un référentiel cohérent de modèles
 * - éviter les doublons via CNIT
 * - garantir la présence des relations obligatoires
 * - optimiser via cache mémoire
 */
class VehicleModelFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Cache mémoire pour limiter les requêtes SQL.
     */
    private array $brandCache = [];
    private array $modelCache = [];
    private array $variantCache = [];
    private array $fuelCache = [];
    private array $gearCache = [];

    /**
     * Cache des CNIT déjà importés.
     */
    private array $cnitCache = [];

    /**
     * Référentiels obligatoires.
     */
    private array $bodyTypes = [];

    public function load(ObjectManager $em): void
    {
        ini_set('memory_limit', '-1');

        $path = dirname(__DIR__, 2) . '/data/utac.csv';

        if (!file_exists($path)) {
            throw new \RuntimeException("CSV introuvable : $path");
        }

        $file = fopen($path, 'r');

        $header = fgetcsv($file, 0, ';');

        /*
         * Nettoyage BOM UTF-8 éventuel
         */
        foreach ($header as &$col) {
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
        }

        /*
         * Mapping colonne => index
         */
        $map = [];

        foreach ($header as $i => $col) {
            $map[strtolower(trim($col))] = $i;
        }

        /*
         * Chargement des BodyTypes
         */
        $this->bodyTypes = $em->getRepository(BodyType::class)->findAll();

        if (empty($this->bodyTypes)) {
            throw new \RuntimeException('Aucun BodyType disponible');
        }

        $output = new ConsoleOutput();

        $progressBar = new ProgressBar($output);
        $progressBar->start();

        $i = 0;

        while (($row = fgetcsv($file, 0, ';')) !== false) {

            /*
             * CNIT utilisé comme clé d’unicité
             */
            $cnit = $this->sanitizeString(
                $this->col($row, $map, 'cnit'),
                50
            );

            if (!$cnit || isset($this->cnitCache[$cnit])) {
                continue;
            }

            $this->cnitCache[$cnit] = true;

            /*
             * Données principales
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

            $fuelName = $this->normalizeFuel(
                $this->col($row, $map, 'energ')
            );

            $gearName = $this->normalizeGear(
                $this->col($row, $map, 'typ_boite_nb_rapp')
            );

            if (!$brandName || !$modelName) {
                continue;
            }

            /*
             * Récupération / création des entités liées
             */
            $brand = $this->getBrand($em, $brandName);

            $model = $this->getModel($em, $brand, $modelName);

            $variant = $this->getVariant($em, $model, $variantName);

            $fuel = $fuelName
                ? $this->getFuel($em, $fuelName)
                : null;

            $gear = $gearName
                ? $this->getGear($em, $gearName)
                : null;

            /*
             * Création VehicleModel
             */
            $vm = new VehicleModel();

            $vm->setBrand($brand);
            $vm->setModel($model);

            /*
             * BodyType obligatoire.
             * Attribution temporaire aléatoire.
             */
            $vm->setBodyType(
                $this->bodyTypes[array_rand($this->bodyTypes)]
            );

            if ($variant) {
                $vm->setVariant($variant);
            }

            if ($fuel) {
                $vm->setFuelType($fuel);
            }

            if ($gear) {
                $vm->setGearType($gear);
            }

            /*
             * Identifiants techniques
             */
            $vm->setCnit($cnit);

            $vm->setUtacCode(
                $this->sanitizeString(
                    $this->col($row, $map, 'tvv'),
                    50
                )
            );

            /*
             * Données techniques
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

            $vm->setMassMin(
                $this->sanitizeNumber(
                    $this->col($row, $map, 'masse_ordma_min'),
                    10000
                )
            );

            $vm->setMassMax(
                $this->sanitizeNumber(
                    $this->col($row, $map, 'masse_ordma_max'),
                    10000
                )
            );

            /*
             * Date homologation
             */
            $date = $this->col($row, $map, 'date_maj');

            if ($date && strtotime($date)) {
                $vm->setHomologationDate(
                    new \DateTime($date)
                );
            }

            $em->persist($vm);

            $i++;
            $progressBar->advance();

            /*
             * Batch flush pour éviter surcharge mémoire
             */
            if ($i % 500 === 0) {

                $em->flush();
                $em->clear();

                $this->resetCache();

                /*
                 * Rechargement des référentiels
                 * après clear()
                 */
                $this->bodyTypes = $em
                    ->getRepository(BodyType::class)
                    ->findAll();
            }
        }

        fclose($file);

        $em->flush();
        $em->clear();

        $progressBar->finish();

        $output->writeln("\nImport terminé.");
    }

    /*
     * =========================
     * HELPERS ENTITÉS
     * =========================
     */

    private function getBrand(
        ObjectManager $em,
        string $name
    ): Brand {
        if (isset($this->brandCache[$name])) {
            return $this->brandCache[$name];
        }

        $brand = $em->getRepository(Brand::class)
            ->findOneBy([
                'name' => $name
            ]);

        if (!$brand) {

            $brand = (new Brand())
                ->setName($name);

            $em->persist($brand);
        }

        return $this->brandCache[$name] = $brand;
    }

    private function getModel(
        ObjectManager $em,
        Brand $brand,
        string $name
    ): Model {
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

        return $this->modelCache[$key] = $model;
    }

    private function getVariant(
        ObjectManager $em,
        Model $model,
        ?string $name
    ): ?Variant {
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

        return $this->variantCache[$key] = $variant;
    }

    private function getFuel(
        ObjectManager $em,
        string $name
    ): FuelType {
        if (isset($this->fuelCache[$name])) {
            return $this->fuelCache[$name];
        }

        $fuel = $em->getRepository(FuelType::class)
            ->findOneBy([
                'name' => $name
            ]);

        if (!$fuel) {

            $fuel = (new FuelType())
                ->setName($name);

            $em->persist($fuel);
        }

        return $this->fuelCache[$name] = $fuel;
    }

    private function getGear(
        ObjectManager $em,
        string $name
    ): GearType {
        if (isset($this->gearCache[$name])) {
            return $this->gearCache[$name];
        }

        $gear = $em->getRepository(GearType::class)
            ->findOneBy([
                'name' => $name
            ]);

        if (!$gear) {

            $gear = (new GearType())
                ->setName($name);

            $em->persist($gear);
        }

        return $this->gearCache[$name] = $gear;
    }

    /*
     * =========================
     * HELPERS UTILS
     * =========================
     */

    private function resetCache(): void
    {
        $this->brandCache = [];
        $this->modelCache = [];
        $this->variantCache = [];
        $this->fuelCache = [];
        $this->gearCache = [];
    }

    private function col(
        array $row,
        array $map,
        string $name
    ): ?string {
        $i = $map[$name] ?? null;

        if ($i === null) {
            return null;
        }

        $value = $row[$i] ?? null;

        return $this->convertEncoding($value);
    }

    /**
     * Conversion automatique en UTF-8.
     */
    private function convertEncoding(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $encoding = mb_detect_encoding(
            $value,
            ['UTF-8', 'ISO-8859-1', 'Windows-1252'],
            true
        );

        if ($encoding && $encoding !== 'UTF-8') {
            $value = mb_convert_encoding(
                $value,
                'UTF-8',
                $encoding
            );
        }

        return trim($value);
    }

    private function normalize(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return mb_convert_case(
            mb_strtolower(trim($value)),
            MB_CASE_TITLE
        );
    }

    private function normalizeFuel(?string $fuel): ?string
    {
        if (!$fuel) {
            return null;
        }

        $fuel = strtolower(trim($fuel));

        return match ($fuel) {

            'ess',
            'es',
            'essence' => 'Essence',

            'go',
            'gazole',
            'diesel' => 'Diesel',

            'el',
            'elec',
            'electric' => 'Électrique',

            'hy',
            'hybride' => 'Hybride',

            'gnv',
            'gn' => 'Gaz Naturel (GNV)',

            'gpl' => 'GPL',

            default => ucfirst($fuel),
        };
    }

    private function normalizeGear(?string $gear): ?string
    {
        if (!$gear) {
            return null;
        }

        $gear = strtolower(trim($gear));

        return match (true) {

            str_contains($gear, 'auto') =>
            'Automatique',

            str_contains($gear, 'man') =>
            'Manuelle',

            str_contains($gear, 'cvt') =>
            'CVT',

            str_contains($gear, 'semi') =>
            'Semi-automatique',

            default =>
            ucfirst($gear),
        };
    }

    private function sanitizeString(
        ?string $value,
        int $max
    ): ?string {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        return mb_strlen($value) > $max
            ? null
            : $value;
    }

    private function sanitizeNumber($value, $max)
    {
        if (!$value) {
            return null;
        }

        $value = str_replace(
            [' ', ','],
            ['', '.'],
            $value
        );

        if (!is_numeric($value)) {
            return null;
        }

        $num = (float) $value;

        return $num > $max
            ? null
            : $num;
    }

    public function getDependencies(): array
    {
        return [
            BodyTypeFixtures::class,
        ];
    }
}
