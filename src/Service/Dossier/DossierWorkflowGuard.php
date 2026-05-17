<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Guard central du workflow Dossier.
 *
 * Rôle :
 * - vérifier toutes les transitions avant exécution
 * - centraliser le debug des erreurs workflow
 * - éviter les comportements silencieux incohérents
 */
class DossierWorkflowGuard
{
    public function __construct(
        private WorkflowInterface $dossierStateMachine,
        private LoggerInterface $logger
    ) {}

    /**
     * Vérifie si une transition est possible.
     */
    public function can(Dossier $dossier, string $transition): bool
    {
        return $this->dossierStateMachine->can($dossier, $transition);
    }

    /**
     * Valide une transition et retourne une erreur explicite si invalide.
     */
    public function assertCan(Dossier $dossier, string $transition): void
    {
        if ($this->can($dossier, $transition)) {
            return;
        }

        $message = sprintf(
            'Workflow error: transition "%s" impossible depuis "%s"',
            $transition,
            $dossier->getStatus()
        );

        $this->logger->error($message, [
            'dossierId' => $dossier->getId(),
            'status' => $dossier->getStatus(),
            'transition' => $transition,
        ]);

        throw new \LogicException($message);
    }

    /**
     * Version safe pour exécution contrôlée.
     */
    public function apply(Dossier $dossier, string $transition, callable $callback): void
    {
        $this->assertCan($dossier, $transition);

        $this->dossierStateMachine->apply($dossier, $transition);

        if ($callback) {
            $callback($dossier);
        }
    }
}
