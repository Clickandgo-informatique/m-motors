<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:build:vehicle-json',
    description: 'Nettoie le CSV UTAC et génère un JSON véhicules propre.'
)]
class BuildVehicleJsonCommand extends Command
{
    private const INPUT_FILE = __DIR__ . '/../../data/utac.csv';
    private const OUTPUT_FILE = __DIR__ . '/../../data/vehicles.json';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!file_exists(self::INPUT_FILE)) {
            $io->error("Fichier introuvable : " . self::INPUT_FILE);
            return Command::FAILURE;
        }

        $handle = fopen(self::INPUT_FILE, 'r');
        if (!$handle) {
            $io->error("Impossible d’ouvrir le fichier CSV.");
            return Command::FAILURE;
        }

        $out = fopen(self::OUTPUT_FILE, 'w');
        if (!$out) {
            fclose($handle);
            $io->error("Impossible d’écrire le fichier JSON.");
            return Command::FAILURE;
        }

        fwrite($out, "[\n");

        $first = true;
        $lineNumber = 0;
        $valid = 0;
        $ignored = 0;

        // Lire l'en-tête brut
        $rawHeader = fgets($handle);
        if ($rawHeader === false) {
            $io->error("Impossible de lire l'en-tête.");
            return Command::FAILURE;
        }

        $cleanHeader = $this->cleanCsvLine($rawHeader);
        $header = explode(';', $cleanHeader);

        $header = array_map(fn($v) => trim($v ?? ''), $header);

        while (($rawLine = fgets($handle)) !== false) {
            $lineNumber++;

            if ($lineNumber % 1000 === 0) {
                $io->writeln("Lignes traitées : $lineNumber");
            }

            // Nettoyage de la ligne brute
            $cleanLine = $this->cleanCsvLine($rawLine);

            if ($cleanLine === '') {
                $ignored++;
                continue;
            }

            $row = explode(';', $cleanLine);

            // Vérifier le nombre de colonnes
            if (count($row) !== count($header)) {
                $ignored++;
                continue;
            }

            $data = array_combine($header, $row);
            $data = array_map(fn($v) => trim($v ?? ''), $data);

            // Champs essentiels
            $brand = ucfirst(strtolower($data['lib_mrq_doss'] ?? ''));
            $model = ucfirst(strtolower($data['lib_mod_doss'] ?? ''));
            $variant = $data['dscom'] ?? '';

            if ($brand === '' || $model === '' || $variant === '') {
                $ignored++;
                continue;
            }

            $jsonLine = json_encode([
                'brand' => $brand,
                'model' => $model,
                'name' => $variant,
                'fuel' => $data['energ'] ?? null,
                'power_hp' => $data['puiss_max'] ?? null,
                'power_fiscal' => $data['puiss_admin'] ?? null,
                'transmission' => $data['typ_boite_nb_rapp'] ?? null,
                'co2' => $data['co2_mixte'] ?? null,
                'consumption' => $data['conso_mixte'] ?? null,
                'mass_min' => $data['masse_ordma_min'] ?? null,
                'mass_max' => $data['masse_ordma_max'] ?? null,
            ], JSON_UNESCAPED_UNICODE);

            if (!$first) {
                fwrite($out, ",\n");
            }
            $first = false;

            fwrite($out, $jsonLine);

            $valid++;
        }

        fwrite($out, "\n]\n");

        fclose($handle);
        fclose($out);

        $io->success("JSON généré avec $valid lignes valides (et $ignored ignorées).");

        return Command::SUCCESS;
    }

    private function cleanCsvLine(string $line): string
    {
        // Supprimer BOM et caractères invisibles
        $line = preg_replace('/[\x00-\x1F\x7F]/u', '', $line ?? '');

        // Trim global
        $line = trim($line ?? '');

        // Normaliser les espaces autour des ;
        $line = preg_replace('/\s*;\s*/', ';', $line);

        // Nettoyer les espaces autour des guillemets
        $line = preg_replace('/\s*"\s*/', '"', $line);

        // Corriger les guillemets internes
        $line = preg_replace('/"{3,}/', '""', $line);

        return $line;
    }
}
