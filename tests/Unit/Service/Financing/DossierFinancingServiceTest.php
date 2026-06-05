<?php

namespace App\Tests\Unit\Service\Financing;

use App\Entity\Dossier;
use App\Entity\Financing;
use App\Service\Financing\DossierFinancingService;
use PHPUnit\Framework\TestCase;

class DossierFinancingServiceTest extends TestCase
{
    public function testSyncFromDossierCreatesFinancingWhenMissing(): void
    {
        // vérifie qu'un financement est créé automatiquement

        $dossier = new Dossier();
        $dossier->setStatus('draft');

        $service = new DossierFinancingService();

        $service->syncFromDossier($dossier);

        $this->assertInstanceOf(
            Financing::class,
            $dossier->getFinancing()
        );

        $this->assertSame(
            $dossier,
            $dossier->getFinancing()->getDossier()
        );
    }

    public function testSyncFromDossierFinancingReviewKeepsPendingStatus(): void
    {
        // vérifie le traitement du statut financing_review

        $dossier = new Dossier();
        $dossier->setStatus('financing_review');

        $financing = new Financing();
        $financing->setStatus('pending');

        $dossier->setFinancing($financing);

        $service = new DossierFinancingService();

        $service->syncFromDossier($dossier);

        $this->assertSame(
            'pending',
            $financing->getStatus()
        );
    }

    public function testSyncFromDossierCompletedApprovesFinancing(): void
    {
        // vérifie qu'un dossier terminé approuve le financement

        $dossier = new Dossier();
        $dossier->setStatus('completed');

        $financing = new Financing();
        $financing->setStatus('pending');

        $dossier->setFinancing($financing);

        $service = new DossierFinancingService();

        $service->syncFromDossier($dossier);

        $this->assertSame(
            'approved',
            $financing->getStatus()
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $financing->getDecidedAt()
        );
    }

    public function testSyncFromDossierCompletedDoesNothingWhenAlreadyApproved(): void
    {
        // vérifie qu'un financement déjà approuvé n'est pas modifié

        $date = new \DateTimeImmutable('-1 day');

        $dossier = new Dossier();
        $dossier->setStatus('completed');

        $financing = new Financing();
        $financing->setStatus('approved');
        $financing->setDecidedAt($date);

        $dossier->setFinancing($financing);

        $service = new DossierFinancingService();

        $service->syncFromDossier($dossier);

        $this->assertSame(
            'approved',
            $financing->getStatus()
        );

        $this->assertSame(
            $date,
            $financing->getDecidedAt()
        );
    }

    public function testSyncFromDossierCancelledRejectsFinancing(): void
    {
        // vérifie qu'un dossier annulé rejette le financement

        $dossier = new Dossier();
        $dossier->setStatus('cancelled');

        $financing = new Financing();
        $financing->setStatus('pending');

        $dossier->setFinancing($financing);

        $service = new DossierFinancingService();

        $service->syncFromDossier($dossier);

        $this->assertSame(
            'rejected',
            $financing->getStatus()
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $financing->getDecidedAt()
        );
    }

    public function testSyncFromDossierWithUnhandledStatus(): void
    {
        // vérifie qu'un statut non géré ne modifie pas le financement

        $dossier = new Dossier();
        $dossier->setStatus('draft');

        $financing = new Financing();
        $financing->setStatus('pending');

        $dossier->setFinancing($financing);

        $service = new DossierFinancingService();

        $service->syncFromDossier($dossier);

        $this->assertSame(
            'pending',
            $financing->getStatus()
        );

        $this->assertNull(
            $financing->getDecidedAt()
        );
    }

    public function testApproveCreatesFinancingWhenMissing(): void
    {
        // vérifie qu'un financement est créé et approuvé

        $dossier = new Dossier();

        $service = new DossierFinancingService();

        $service->approve($dossier);

        $financing = $dossier->getFinancing();

        $this->assertInstanceOf(
            Financing::class,
            $financing
        );

        $this->assertSame(
            'approved',
            $financing->getStatus()
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $financing->getDecidedAt()
        );

        $this->assertSame(
            $dossier,
            $financing->getDossier()
        );
    }

    public function testApproveUpdatesExistingFinancing(): void
    {
        // vérifie l'approbation d'un financement existant

        $dossier = new Dossier();

        $financing = new Financing();
        $financing->setStatus('pending');

        $dossier->setFinancing($financing);

        $service = new DossierFinancingService();

        $service->approve($dossier);

        $this->assertSame(
            'approved',
            $financing->getStatus()
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $financing->getDecidedAt()
        );
    }
}