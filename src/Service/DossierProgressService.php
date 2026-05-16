<?php

namespace App\Service;

use App\Entity\Dossier;

class DossierProgressService
{
    private const STEPS = [
        'draft' => [
            'label' => 'Brouillon',
            'progress' => 5,
        ],
        'vehicle_selected' => [
            'label' => 'Véhicule sélectionné',
            'progress' => 20,
        ],
        'documents_pending' => [
            'label' => 'Documents à fournir',
            'progress' => 40,
        ],
        'documents_review' => [
            'label' => 'Documents en validation',
            'progress' => 60,
        ],
        'financing_review' => [
            'label' => 'Étude du financement',
            'progress' => 80,
        ],
        'completed' => [
            'label' => 'Dossier terminé',
            'progress' => 100,
        ],
        'cancelled' => [
            'label' => 'Dossier annulé',
            'progress' => 0,
        ],
    ];

    public function getProgress(Dossier $dossier): int
    {
        return self::STEPS[$dossier->getStatus()]['progress'] ?? 0;
    }

    public function getLabel(Dossier $dossier): string
    {
        return self::STEPS[$dossier->getStatus()]['label'] ?? 'Inconnu';
    }

    public function getSteps(): array
    {
        return self::STEPS;
    }

    public function isCompleted(string $step, Dossier $dossier): bool
    {
        $steps = array_keys(self::STEPS);

        return array_search($step, $steps, true)
            <= array_search($dossier->getStatus(), $steps, true);
    }
}
