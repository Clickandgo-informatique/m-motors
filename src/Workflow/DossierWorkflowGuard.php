<?php

namespace App\Workflow\Guard;

use App\Entity\Dossier;
use App\Service\DossierDocumentValidator;
use Symfony\Component\Workflow\Event\GuardEvent;

class DossierWorkflowGuard
{
    public function __construct(
        private DossierDocumentValidator $validator
    ) {}

    public function guardSubmitDocuments(GuardEvent $event): void
    {
        /** @var Dossier $dossier */
        $dossier = $event->getSubject();

        if (!$this->validator->isComplete($dossier)) {
            $event->setBlocked(true, 'Documents incomplets');
        }
    }
}
