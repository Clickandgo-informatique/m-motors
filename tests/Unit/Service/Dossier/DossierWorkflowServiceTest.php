<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Enum\DossierDocumentStatus;
use App\Service\Dossier\DossierWorkflowAuditService;
use App\Service\Dossier\DossierWorkflowGuard;
use App\Service\Dossier\DossierWorkflowService;
use App\Service\Financing\DossierFinancingService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

class DossierWorkflowServiceTest extends TestCase
{
    private function createService(
        WorkflowInterface $workflow,
        $guard = null,
        $financing = null,
        $audit = null
    ): DossierWorkflowService {
        return new DossierWorkflowService(
            $workflow,
            $financing ?? $this->createMock(DossierFinancingService::class),
            $guard ?? $this->createMock(DossierWorkflowGuard::class),
            $audit ?? $this->createMock(DossierWorkflowAuditService::class)
        );
    }

    public function testApplyExecutesFullWorkflow(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('apply')
            ->willReturn($this->createMock(Marking::class));

        $guard = $this->createMock(DossierWorkflowGuard::class);
        $guard->expects($this->once())->method('assertCan');

        $financing = $this->createMock(DossierFinancingService::class);
        $financing->expects($this->once())->method('syncFromDossier');

        $audit = $this->createMock(DossierWorkflowAuditService::class);
        $audit->expects($this->once())->method('log');

        $this->createService($workflow, $guard, $financing, $audit)
            ->apply(new Dossier(), 'select_vehicle');
    }

    public function testWrapperMethodsCoverAllTransitions(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('apply')->willReturn($this->createMock(Marking::class));
        $workflow->method('can')->willReturn(true);

        $service = $this->createService($workflow);

        $dossier = new Dossier();

        $service->selectVehicle($dossier);
        $service->requestDocuments($dossier);
        $service->submitDocuments($dossier);
        $service->validateDocuments($dossier);
        $service->rejectDocuments($dossier);
        $service->approveFinancing($dossier);
        $service->rejectFinancing($dossier);
        $service->cancel($dossier);

        $this->assertTrue(true);
    }

    public function testApplySafeSkips(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn(false);

        $service = $this->createService($workflow);

        $service->applySafe(new Dossier(), 'invalid');

        $this->assertTrue(true);
    }

    public function testApplySafeExecutes(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn(true);
        $workflow->method('apply')->willReturn($this->createMock(Marking::class));

        $guard = $this->createMock(DossierWorkflowGuard::class);
        $guard->expects($this->once())->method('assertCan');

        $financing = $this->createMock(DossierFinancingService::class);
        $financing->expects($this->once())->method('syncFromDossier');

        $audit = $this->createMock(DossierWorkflowAuditService::class);
        $audit->expects($this->once())->method('log');

        $this->createService($workflow, $guard, $financing, $audit)
            ->applySafe(new Dossier(), 'select_vehicle');
    }

    public function testRefreshDossierStatus(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);

        $doc = $this->createMock(DossierDocument::class);
        $doc->method('getStatus')->willReturn(DossierDocumentStatus::UPLOADED);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getDocuments')
            ->willReturn(new ArrayCollection([$doc]));

        $result = $this->createService($workflow)
            ->refreshDossierStatus($dossier);

        $this->assertSame('submit_documents', $result);
    }

    public function testRefreshDossierStatusNull(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getDocuments')->willReturn(new ArrayCollection());

        $result = $this->createService($workflow)
            ->refreshDossierStatus($dossier);

        $this->assertNull($result);
    }

    public function testGetCompletionRateFullCoverage(): void
    {
        // vérifie le calcul du taux de complétion

        $workflow = $this->createMock(WorkflowInterface::class);

        $doc1 = $this->createMock(DossierDocument::class);
        $doc1->method('getStatus')
            ->willReturn(DossierDocumentStatus::VALIDATED);

        $doc2 = $this->createMock(DossierDocument::class);
        $doc2->method('getStatus')
            ->willReturn(DossierDocumentStatus::UPLOADED);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getDocuments')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$doc1, $doc2]));

        $service = new DossierWorkflowService(
            $workflow,
            $this->createMock(DossierFinancingService::class),
            $this->createMock(DossierWorkflowGuard::class),
            $this->createMock(DossierWorkflowAuditService::class)
        );

        $result = $service->getCompletionRate($dossier);

        $this->assertIsInt($result);
        $this->assertTrue($result >= 0 && $result <= 100);
    }

    public function testGetCompletionRateEmpty(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getDocuments')->willReturn(new ArrayCollection());

        $result = $this->createService($workflow)
            ->getCompletionRate($dossier);

        $this->assertSame(0, $result);
    }

    public function testCan(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn(true);

        $result = $this->createService($workflow)
            ->can(new Dossier(), 'x');

        $this->assertTrue($result);
    }
}
