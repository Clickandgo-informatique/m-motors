<?php

namespace App\Enum;

enum DossierDocumentType: string
{
    case UPLOAD = 'upload';
    case IDENTITY = 'identity';
    case CONTRACT = 'contract';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::UPLOAD => 'Upload',
            self::IDENTITY => 'Pièce d’identité',
            self::CONTRACT => 'Contrat',
            self::OTHER => 'Autre',
        };
    }
}
