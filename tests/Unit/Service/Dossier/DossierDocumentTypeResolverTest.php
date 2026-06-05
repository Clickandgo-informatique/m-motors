<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Enum\DossierDocumentType;
use App\Service\Dossier\DossierDocumentTypeResolver;
use PHPUnit\Framework\TestCase;

class DossierDocumentTypeResolverTest extends TestCase
{
    public function testResolveIdentityForVehicleSelected(): void
    {
        // vérifie le type de document requis après sélection du véhicule

        $dossier = new Dossier();
        $dossier->setStatus('vehicle_selected');

        $resolver = new DossierDocumentTypeResolver();

        $this->assertSame(
            DossierDocumentType::IDENTITY,
            $resolver->resolve($dossier)
        );
    }

    public function testResolveContractForDocumentsPending(): void
    {
        // vérifie le type de document requis lorsque les documents sont en attente

        $dossier = new Dossier();
        $dossier->setStatus('documents_pending');

        $resolver = new DossierDocumentTypeResolver();

        $this->assertSame(
            DossierDocumentType::CONTRACT,
            $resolver->resolve($dossier)
        );
    }

    public function testResolveContractForDocumentsReview(): void
    {
        // vérifie le type de document requis lors de la revue documentaire

        $dossier = new Dossier();
        $dossier->setStatus('documents_review');

        $resolver = new DossierDocumentTypeResolver();

        $this->assertSame(
            DossierDocumentType::CONTRACT,
            $resolver->resolve($dossier)
        );
    }

    public function testResolveUploadForUnknownStatus(): void
    {
        // vérifie le type de document par défaut pour un statut non géré

        $dossier = new Dossier();
        $dossier->setStatus('completed');

        $resolver = new DossierDocumentTypeResolver();

        $this->assertSame(
            DossierDocumentType::UPLOAD,
            $resolver->resolve($dossier)
        );
    }
}
