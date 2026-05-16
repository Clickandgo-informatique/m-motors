<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Service\Financing\DossierFinancingService;
use Symfony\Component\Workflow\WorkflowInterface;

class DossierWorkflowService
{
    public function __construct(
        private WorkflowInterface $dossierStateMachine,
         private DossierFinancingService $financingService
    ) {}

    // =========================================================
    // TRANSITION GENERIQUE
    // =========================================================

    public function apply(Dossier $dossier, string $transition): void
    {
        if (!$this->dossierStateMachine->can($dossier, $transition)) {
            throw new \LogicException(sprintf(
                'Transition "%s" impossible pour le statut "%s"',
                $transition,
                $dossier->getStatus()
            ));
        }

        $this->dossierStateMachine->apply($dossier, $transition);
        $this->financingService->syncFromDossier($dossier);
    }

    // =========================================================
    // HELPERS METIER
    // =========================================================

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
}
