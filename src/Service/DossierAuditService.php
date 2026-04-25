<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\DossierAudit;
use App\Entity\DossierDocument;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DossierAuditService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function log(
        Dossier $dossier,
        string $action,
        ?User $user = null,
        ?string $message = null,
        ?DossierDocument $document = null
    ): void {
        $audit = new DossierAudit();
        $audit->setDossier($dossier);
        $audit->setAction($action);
        $audit->setUser($user);
        $audit->setMessage($message);
        $audit->setDocument($document);

        $this->em->persist($audit);
        $this->em->flush();
    }
}
