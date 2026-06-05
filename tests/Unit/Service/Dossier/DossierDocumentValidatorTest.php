<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Enum\DossierDocumentType;
use App\Service\Dossier\DossierDocumentValidator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class DossierDocumentValidatorTest extends TestCase
{
    public function testGetRequiredDocuments(): void
    {
        // vérifie les documents obligatoires

        $dossier = new Dossier();

        $validator = new DossierDocumentValidator();

        $this->assertSame(
            [
                DossierDocumentType::IDENTITY,
                DossierDocumentType::CONTRACT,
            ],
            $validator->getRequiredDocuments($dossier)
        );
    }

    public function testIsCompleteReturnsTrue(): void
    {
        // vérifie qu'un dossier est complet

        $dossier = new Dossier();

        $identity = new DossierDocument();
        $identity->setDocumentType(
            DossierDocumentType::IDENTITY
        );

        $contract = new DossierDocument();
        $contract->setDocumentType(
            DossierDocumentType::CONTRACT
        );

        $this->setDocuments(
            $dossier,
            [$identity, $contract]
        );

        $validator = new DossierDocumentValidator();

        $this->assertTrue(
            $validator->isComplete($dossier)
        );
    }

    public function testIsCompleteReturnsFalseWhenIdentityIsMissing(): void
    {
        // vérifie qu'un dossier est incomplet sans pièce d'identité

        $dossier = new Dossier();

        $contract = new DossierDocument();
        $contract->setDocumentType(
            DossierDocumentType::CONTRACT
        );

        $this->setDocuments(
            $dossier,
            [$contract]
        );

        $validator = new DossierDocumentValidator();

        $this->assertFalse(
            $validator->isComplete($dossier)
        );
    }

    public function testIsCompleteReturnsFalseWhenContractIsMissing(): void
    {
        // vérifie qu'un dossier est incomplet sans contrat

        $dossier = new Dossier();

        $identity = new DossierDocument();
        $identity->setDocumentType(
            DossierDocumentType::IDENTITY
        );

        $this->setDocuments(
            $dossier,
            [$identity]
        );

        $validator = new DossierDocumentValidator();

        $this->assertFalse(
            $validator->isComplete($dossier)
        );
    }

    public function testIsCompleteReturnsFalseWhenNoDocumentsExist(): void
    {
        // vérifie qu'un dossier vide est incomplet

        $dossier = new Dossier();

        $validator = new DossierDocumentValidator();

        $this->assertFalse(
            $validator->isComplete($dossier)
        );
    }

    private function setDocuments(
        Dossier $dossier,
        array $documents
    ): void {
        $property = new ReflectionProperty(
            Dossier::class,
            'documents'
        );

        $property->setAccessible(true);

        $property->setValue(
            $dossier,
            new ArrayCollection($documents)
        );
    }
}
