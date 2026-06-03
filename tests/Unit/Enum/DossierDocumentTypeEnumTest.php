<?php

namespace App\Tests\Unit\Enum;

use App\Enum\DossierDocumentType;
use PHPUnit\Framework\TestCase;

class DossierDocumentTypeEnumTest extends TestCase
{
    public function testUploadLabel(): void
    {
        // vérifie le libellé du type upload

        $this->assertSame(
            'Upload',
            DossierDocumentType::UPLOAD->label()
        );
    }

    public function testIdentityLabel(): void
    {
        // vérifie le libellé du type pièce d'identité

        $this->assertSame(
            'Pièce d’identité',
            DossierDocumentType::IDENTITY->label()
        );
    }

    public function testContractLabel(): void
    {
        // vérifie le libellé du type contrat

        $this->assertSame(
            'Contrat',
            DossierDocumentType::CONTRACT->label()
        );
    }

    public function testOtherLabel(): void
    {
        // vérifie le libellé du type autre

        $this->assertSame(
            'Autre',
            DossierDocumentType::OTHER->label()
        );
    }

    public function testAllLabelsAreDefined(): void
    {
        // vérifie que chaque valeur de l'enum retourne un libellé non vide

        foreach (DossierDocumentType::cases() as $type) {
            $this->assertNotSame('', $type->label());
        }
    }
}