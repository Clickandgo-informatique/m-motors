<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Service\Dossier\DossierWorkflowAuditService;
use App\Service\Financing\DossierFinancingService;
use Symfony\Component\Workflow\WorkflowInterface;


class DossierWorkflowService
{
    public function __construct(
        private WorkflowInterface $dossierStateMachine,
        private DossierFinancingService $financingService,
        private DossierWorkflowGuard $guard,
        private DossierWorkflowAuditService $audit
    ) {}
    /**
     * Applique une transition du workflow dossier.
     *
     * Règle :
     * - toute transition est validée par le guard central
     * - aucune logique implicite ici
     * - le workflow Symfony est la seule source d'exécution
     */
    public function apply(Dossier $dossier, string $transition): void
    {
        $this->guard->assertCan($dossier, $transition);
        $from = $dossier->getStatus();
        $this->dossierStateMachine->apply($dossier, $transition);
        $to = $dossier->getStatus();
        $this->financingService->syncFromDossier($dossier);

        $this->audit->log(
            $dossier,
            $transition,
            $from,
            $to
        );
    }

    public function selectVehicle(Dossier $dossier): void
    {
        $this->apply($dossier, 'select_vehicle');
    }

    public function requestDocuments(Dossier $dossier): void
    {
        $this->apply($dossier, 'request_documents');
    }

    public function submitDocuments(Dossier $dossier): void
    {
        $this->apply($dossier, 'submit_documents');
    }

    public function validateDocuments(Dossier $dossier): void
    {
        $this->apply($dossier, 'validate_documents');
    }

    public function rejectDocuments(Dossier $dossier): void
    {
        $this->apply($dossier, 'reject_documents');
    }

    public function approveFinancing(Dossier $dossier): void
    {
        $this->apply($dossier, 'approve_financing');
    }

    public function rejectFinancing(Dossier $dossier): void
    {
        $this->apply($dossier, 'reject_financing');
    }

    public function cancel(Dossier $dossier): void
    {
        $this->apply($dossier, 'cancel');
    }

    /**
     * IMPORTANT :
     * Cette méthode NE DOIT PAS déclencher de transition.
     * Elle retourne uniquement une intention métier.
     */
    public function refreshDossierStatus(Dossier $dossier): ?string
    {
        $documents = $dossier->getDocuments();

        if ($documents->isEmpty()) {
            return null;
        }

        foreach ($documents as $document) {
            if ($document->getStatus()->value === 'uploaded') {
                return 'submit_documents';
            }
        }

        return null;
    }

    public function getCompletionRate(Dossier $dossier): int
    {
        $documents = $dossier->getDocuments();

        if ($documents->isEmpty()) {
            return 0;
        }

        $total = count($documents);
        $valid = 0;

        foreach ($documents as $document) {
            if (
                method_exists($document->getStatus(), 'isFinal')
                && $document->getStatus()->isFinal()
            ) {
                $valid++;
            }
        }

        return (int) round(($valid / $total) * 100);
    }
    public function can(Dossier $dossier, string $transition): bool
    {
        return $this->dossierStateMachine->can($dossier, $transition);
    }

    public function applySafe(Dossier $dossier, string $transition): void
    {
        if (!$this->can($dossier, $transition)) {
            return;
        }

        $this->apply($dossier, $transition);
    }
}
