<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Enum\DossierDocumentType;

class DossierDocumentTypeResolver
{
    public function resolve(Dossier $dossier): DossierDocumentType
    {
        return match ($dossier->getStatus()) {
            'vehicle_selected' => DossierDocumentType::IDENTITY,
            'documents_pending' => DossierDocumentType::CONTRACT,
            'documents_review' => DossierDocumentType::CONTRACT,
            default => DossierDocumentType::UPLOAD,
        };
    }
}
