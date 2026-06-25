<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierWorkflowLog;
use App\Entity\User;
use App\Enum\DossierStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service d’audit des transitions du workflow Dossier.
 *
 * Objectif :
 * - tracer toutes les transitions Symfony Workflow
 * - enregistrer from/to status
 * - associer éventuellement l'utilisateur connecté
 */
class DossierWorkflowAuditService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function log(
        Dossier $dossier,
        string $transition,
        DossierStatus $fromStatus,
        DossierStatus $toStatus
    ): void {
        $user = $this->security->getUser();

        $userId = null;

        if ($user instanceof User) {
            $userId = $user->getId();
        }

        $log = new DossierWorkflowLog();
        $log->setDossier($dossier);
        $log->setTransition($transition);
        $log->setFromStatus($fromStatus->value);
        $log->setToStatus($toStatus->value);
        $log->setUserId($userId);

        $this->em->persist($log);
        $this->em->flush();
    }
}
