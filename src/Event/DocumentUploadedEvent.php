<?php

namespace App\Event;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use Symfony\Contracts\EventDispatcher\Event;

class DocumentUploadedEvent extends Event
{
    public function __construct(
        private DossierDocument $document,
        private Dossier $dossier
    ) {}

    public function getDocument(): DossierDocument
    {
        return $this->document;
    }

    public function getDossier(): Dossier
    {
        return $this->dossier;
    }
}
