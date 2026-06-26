<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * service central du workflow dossier
 *
 * rôle :
 * - déclencher les transitions
 * - déléguer les effets au workflow Symfony
 * - aucune logique métier cachée
 */
class DossierWorkflowService
{
    public function __construct(
        private WorkflowInterface $dossierStateMachine,
        private DossierWorkflowGuard $guard
    ) {}

    /**
     * applique une transition
     */
    public function apply(Dossier $dossier, string $transition): void
    {
        $this->guard->assertCan($dossier, $transition);

        $this->dossierStateMachine->apply($dossier, $transition);
    }

    /**
     * vérifie si une transition est possible
     */
    public function can(Dossier $dossier, string $transition): bool
    {
        return $this->dossierStateMachine->can($dossier, $transition);
    }

    /**
     * application sécurisée
     */
    public function applySafe(Dossier $dossier, string $transition): void
    {
        if (!$this->can($dossier, $transition)) {
            return;
        }

        $this->apply($dossier, $transition);
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

    public function signOrder(Dossier $dossier): void
    {
        $this->apply($dossier, 'sign_order');
    }

    public function cancel(Dossier $dossier): void
    {
        $this->apply($dossier, 'cancel');
    }
}
