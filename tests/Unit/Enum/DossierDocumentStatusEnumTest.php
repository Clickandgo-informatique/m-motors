<?php

namespace App\Tests\Unit\Enum;

use App\Enum\DossierDocumentStatus;
use PHPUnit\Framework\TestCase;

class DossierDocumentStatusEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie tous les libellés métier

        $this->assertSame(
            'Envoyé',
            DossierDocumentStatus::UPLOADED->label()
        );

        $this->assertSame(
            'Validé',
            DossierDocumentStatus::VALIDATED->label()
        );

        $this->assertSame(
            'Refusé',
            DossierDocumentStatus::REJECTED->label()
        );

        $this->assertSame(
            'Manquant',
            DossierDocumentStatus::MISSING->label()
        );
    }

    public function testBadges(): void
    {
        // vérifie les classes bootstrap associées

        $this->assertSame(
            'primary',
            DossierDocumentStatus::UPLOADED->badge()
        );

        $this->assertSame(
            'success',
            DossierDocumentStatus::VALIDATED->badge()
        );

        $this->assertSame(
            'danger',
            DossierDocumentStatus::REJECTED->badge()
        );

        $this->assertSame(
            'secondary',
            DossierDocumentStatus::MISSING->badge()
        );
    }

    public function testValidatedIsFinal(): void
    {
        // un document validé est dans un état final

        $this->assertTrue(
            DossierDocumentStatus::VALIDATED->isFinal()
        );
    }

    public function testRejectedIsFinal(): void
    {
        // un document refusé est dans un état final

        $this->assertTrue(
            DossierDocumentStatus::REJECTED->isFinal()
        );
    }

    public function testUploadedIsNotFinal(): void
    {
        // un document envoyé reste en attente de traitement

        $this->assertFalse(
            DossierDocumentStatus::UPLOADED->isFinal()
        );
    }

    public function testMissingIsNotFinal(): void
    {
        // un document manquant n'est pas un état final

        $this->assertFalse(
            DossierDocumentStatus::MISSING->isFinal()
        );
    }

    public function testUploadedIsPending(): void
    {
        // un document envoyé est en attente

        $this->assertTrue(
            DossierDocumentStatus::UPLOADED->isPending()
        );
    }

    public function testMissingIsPending(): void
    {
        // un document manquant est considéré en attente

        $this->assertTrue(
            DossierDocumentStatus::MISSING->isPending()
        );
    }

    public function testValidatedIsNotPending(): void
    {
        // un document validé n'est plus en attente

        $this->assertFalse(
            DossierDocumentStatus::VALIDATED->isPending()
        );
    }

    public function testRejectedIsNotPending(): void
    {
        // un document refusé n'est plus en attente

        $this->assertFalse(
            DossierDocumentStatus::REJECTED->isPending()
        );
    }
}
