<?php

namespace App\Service;

use App\Entity\DossierDocument;

class DocumentAuditService
{
    public function logUpload(DossierDocument $document): void
    {
        // simple log (remplaçable par entity Audit)
        error_log(sprintf(
            'Document uploadé: %s (Dossier %s)',
            $document->getOriginalName(),
            $document->getDossier()->getId()
        ));
    }
}
