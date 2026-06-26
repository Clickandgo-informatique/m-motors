<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierWorkflowLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * service d’audit du workflow dossier
 *
 * rôle :
 * - créer un log de transition
 * - aucune logique métier
 * - aucune gestion de transaction
 */
class DossierWorkflowAuditService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    /**
     * crée un log de transition workflow
     */
    public function log(
        Dossier $dossier,
        string $transition,
        string $fromStatus,
        string $toStatus
    ): void {
        $user = $this->security->getUser();

        $userId = $user instanceof User ? $user->getId() : null;

        $log = new DossierWorkflowLog();

        $log->setDossier($dossier);
        $log->setTransition($transition);
        $log->setFromStatus($fromStatus);
        $log->setToStatus($toStatus);
        $log->setUserId($userId);

        $this->em->persist($log);
    }
}
