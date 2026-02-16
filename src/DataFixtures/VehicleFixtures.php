<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Model;
use App\Entity\Variant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VehicleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        ini_set('memory_limit', '512M'); // TEMPORAIRE pour voir la vraie erreur

        $file = __DIR__ . '/../../data/vehicles.csv';
        $handle = fopen($file, 'r');

        if (!$handle) {
            throw new \Exception("Impossible d’ouvrir le fichier CSV");
        }

        // Lecture de l’en-tête
        $header = fgetcsv($handle, separator: ';');

        // Nettoyage BOM + trim
        $header = array_map(function ($col) {
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            return trim($col);
        }, $header);

        $expectedColumns = count($header);
        $batchSize = 200;
        $i = 0;

        while (($row = fgetcsv($handle, separator: ';')) !== false) {

            // --- Vérification du nombre de colonnes ---
            if (count($row) !== $expectedColumns) {
                throw new \Exception(
                    "❌ Ligne CSV invalide : nombre de colonnes incorrect\n" .
                        "Header = $expectedColumns colonnes\n" .
                        "Row = " . count($row) . " colonnes\n" .
                        "Contenu de la ligne : " . implode(' | ', $row)
                );
            }

            // --- array_combine sécurisé ---
            $data = array_combine($header, $row);

            if ($data === false) {
                throw new \Exception("❌ array_combine a échoué — header/row mismatch");
            }

            // --- Vérification des colonnes obligatoires ---
            foreach (['brand', 'model', 'body_type', 'fuel_type', 'powertrain_type'] as $col) {
                if (!array_key_exists($col, $data)) {
                    throw new \Exception("❌ Colonne manquante dans le CSV : '$col'");
                }
            }

            // --- BRAND ---
            $brand = $manager->getRepository(Brand::class)
                ->findOneBy(['name' => $data['brand']]);

            if (!$brand) {
                $brand = new Brand();
                $brand->setName($data['brand']);
                $manager->persist($brand);
            }

            // --- MODEL ---
            $model = $manager->getRepository(Model::class)
                ->findOneBy([
                    'name' => $data['model'],
                    'brand' => $brand
                ]);

            if (!$model) {
                $model = new Model();
                $model->setName($data['model']);
                $model->setBrand($brand);
                $manager->persist($model);
            }

            // --- VARIANT ---
            $variant = new Variant();
            $variant->setName(
                $data['body_type'] . ' ' . $data['fuel_type'] . ' ' . $data['powertrain_type']
            );
            $variant->setModel($model);

            $manager->persist($variant);

            // --- BATCH PROCESSING ---
            if (($i % $batchSize) === 0) {
                $manager->flush();
                $manager->clear();
            }

            $i++;
        }

        fclose($handle);

        // Flush final
        $manager->flush();
        $manager->clear();
    }
}
