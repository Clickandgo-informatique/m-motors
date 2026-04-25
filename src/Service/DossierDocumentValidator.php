<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Enum\DossierDocumentType;

class DossierDocumentValidator
{
    /**
     * Règles métier simples (à adapter selon DossierType si besoin)
     */
    public function getRequiredDocuments(Dossier $dossier): array
    {
        return match ($dossier->getType()) {
            default => [
                DossierDocumentType::IDENTITY,
                DossierDocumentType::CONTRACT,
            ],
        };
    }

    /**
     * Vérifie complétude
     */
    public function isComplete(Dossier $dossier): bool
    {
        $required = $this->getRequiredDocuments($dossier);

        $uploaded = array_map(
            fn($doc) => $doc->getDocumentType(),
            $dossier->getDocuments()->toArray()
        );

        foreach ($required as $type) {
            if (!in_array($type, $uploaded, true)) {
                return false;
            }
        }

        return true;
    }
}
