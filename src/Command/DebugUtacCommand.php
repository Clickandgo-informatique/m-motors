<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug:utac',
    description: 'Debug du fichier UTAC pour comprendre pourquoi le JSON est vide.'
)]
class DebugUtacCommand extends Command
{
    private const INPUT_FILE = __DIR__ . '/../../data/utac.csv';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!file_exists(self::INPUT_FILE)) {
            $io->error("Fichier introuvable : " . self::INPUT_FILE);
            return Command::FAILURE;
        }

        $lines = file(self::INPUT_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $header = str_getcsv(array_shift($lines), ';');
        $header = array_map('trim', $header);

        $io->writeln("Colonnes détectées (" . count($header) . ") :");
        $io->writeln(implode(' | ', $header));
        $io->newLine();

        $i = 0;

        foreach ($lines as $line) {
            $i++;

            $row = str_getcsv($line, ';');

            $io->writeln("Ligne $i :");
            $io->writeln("Colonnes trouvées : " . count($row));
            $io->writeln("Contenu : " . implode(' | ', $row));

            if (count($row) !== count($header)) {
                $io->error("IGNORÉ : nombre de colonnes incorrect");
            } else {
                $io->success("OK : ligne valide");
            }

            $io->newLine();

            if ($i >= 10) break; // on affiche seulement les 10 premières lignes
        }

        return Command::SUCCESS;
    }
}
