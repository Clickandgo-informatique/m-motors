<?php

namespace App\Event;

use App\Entity\DossierDocument;

class DocumentUploadedEvent
{
    public function __construct(
        private DossierDocument $document
    ) {}

    public function getDocument(): DossierDocument
    {
        return $this->document;
    }
}
