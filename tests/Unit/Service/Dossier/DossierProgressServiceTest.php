<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Service\Dossier\DossierProgressService;
use PHPUnit\Framework\TestCase;

class DossierProgressServiceTest extends TestCase
{
    public function testGetProgressForKnownStatus(): void
    {
        // vérifie le pourcentage d'avancement d'un statut connu

        $dossier = new Dossier();
        $dossier->setStatus('documents_review');

        $service = new DossierProgressService();

        $this->assertSame(
            60,
            $service->getProgress($dossier)
        );
    }

    public function testGetProgressForUnknownStatus(): void
    {
        // vérifie la valeur par défaut pour un statut inconnu

        $dossier = new Dossier();
        $dossier->setStatus('unknown_status');

        $service = new DossierProgressService();

        $this->assertSame(
            0,
            $service->getProgress($dossier)
        );
    }

    public function testGetLabelForKnownStatus(): void
    {
        // vérifie le libellé d'un statut connu

        $dossier = new Dossier();
        $dossier->setStatus('financing_review');

        $service = new DossierProgressService();

        $this->assertSame(
            'Étude du financement',
            $service->getLabel($dossier)
        );
    }

    public function testGetLabelForUnknownStatus(): void
    {
        // vérifie le libellé par défaut pour un statut inconnu

        $dossier = new Dossier();
        $dossier->setStatus('unknown_status');

        $service = new DossierProgressService();

        $this->assertSame(
            'Inconnu',
            $service->getLabel($dossier)
        );
    }

    public function testGetSteps(): void
    {
        // vérifie le contenu de la configuration des étapes

        $service = new DossierProgressService();

        $steps = $service->getSteps();

        $this->assertArrayHasKey(
            'draft',
            $steps
        );

        $this->assertArrayHasKey(
            'completed',
            $steps
        );

        $this->assertSame(
            100,
            $steps['completed']['progress']
        );

        $this->assertSame(
            'Dossier terminé',
            $steps['completed']['label']
        );
    }

    public function testIsCompletedReturnsTrueForPreviousStep(): void
    {
        // vérifie qu'une étape antérieure est considérée comme réalisée

        $dossier = new Dossier();
        $dossier->setStatus('documents_review');

        $service = new DossierProgressService();

        $this->assertTrue(
            $service->isCompleted(
                'vehicle_selected',
                $dossier
            )
        );
    }

    public function testIsCompletedReturnsTrueForCurrentStep(): void
    {
        // vérifie que l'étape courante est considérée comme réalisée

        $dossier = new Dossier();
        $dossier->setStatus('documents_review');

        $service = new DossierProgressService();

        $this->assertTrue(
            $service->isCompleted(
                'documents_review',
                $dossier
            )
        );
    }

    public function testIsCompletedReturnsFalseForFutureStep(): void
    {
        // vérifie qu'une étape future n'est pas encore réalisée

        $dossier = new Dossier();
        $dossier->setStatus('vehicle_selected');

        $service = new DossierProgressService();

        $this->assertFalse(
            $service->isCompleted(
                'completed',
                $dossier
            )
        );
    }
}
