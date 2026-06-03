<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Enum\DossierDocumentStatus;
use App\Enum\DossierDocumentType;
use PHPUnit\Framework\TestCase;

class DossierDocumentTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $document = new DossierDocument();

        self::assertNull($document->getId());

        self::assertSame(
            DossierDocumentType::UPLOAD,
            $document->getDocumentType()
        );

        self::assertSame(
            DossierDocumentStatus::UPLOADED,
            $document->getStatus()
        );

        self::assertNull($document->getDossier());
        self::assertNull($document->getOriginalName());
        self::assertNull($document->getFileName());
        self::assertNull($document->getPath());
    }

    // vérifie l'association au dossier
    public function testDossier(): void
    {
        $document = new DossierDocument();
        $dossier = new Dossier();

        $document->setDossier($dossier);

        self::assertSame(
            $dossier,
            $document->getDossier()
        );
    }

    // vérifie qu'un dossier peut être null
    public function testDossierCanBeNull(): void
    {
        $document = new DossierDocument();

        $document->setDossier(null);

        self::assertNull($document->getDossier());
    }

    // vérifie le type de document
    public function testDocumentType(): void
    {
        $document = new DossierDocument();

        $document->setDocumentType(
            DossierDocumentType::UPLOAD
        );

        self::assertSame(
            DossierDocumentType::UPLOAD,
            $document->getDocumentType()
        );
    }

    // vérifie le nom original
    public function testOriginalName(): void
    {
        $document = new DossierDocument();

        $document->setOriginalName('piece-identite.pdf');

        self::assertSame(
            'piece-identite.pdf',
            $document->getOriginalName()
        );
    }

    // vérifie le nom du fichier stocké
    public function testFileName(): void
    {
        $document = new DossierDocument();

        $document->setFileName('uuid-file.pdf');

        self::assertSame(
            'uuid-file.pdf',
            $document->getFileName()
        );
    }

    // vérifie le chemin du fichier
    public function testPath(): void
    {
        $document = new DossierDocument();

        $document->setPath('/uploads/dossiers/test.pdf');

        self::assertSame(
            '/uploads/dossiers/test.pdf',
            $document->getPath()
        );
    }

    // vérifie le statut uploaded
    public function testStatusUploaded(): void
    {
        $document = new DossierDocument();

        $document->setStatus(
            DossierDocumentStatus::UPLOADED
        );

        self::assertSame(
            DossierDocumentStatus::UPLOADED,
            $document->getStatus()
        );
    }

    // vérifie le statut validated
    public function testStatusValidated(): void
    {
        $document = new DossierDocument();

        $before = new \DateTimeImmutable();

        $document->setStatus(
            DossierDocumentStatus::VALIDATED
        );

        $after = new \DateTimeImmutable();

        self::assertSame(
            DossierDocumentStatus::VALIDATED,
            $document->getStatus()
        );

        $reflection = new \ReflectionClass($document);

        $property = $reflection->getProperty('validatedAt');

        $property->setAccessible(true);

        $validatedAt = $property->getValue($document);

        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $validatedAt
        );

        self::assertGreaterThanOrEqual(
            $before->getTimestamp(),
            $validatedAt->getTimestamp()
        );

        self::assertLessThanOrEqual(
            $after->getTimestamp(),
            $validatedAt->getTimestamp()
        );
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $document = new DossierDocument();

        self::assertSame(
            $document,
            $document->setOriginalName('test.pdf')
        );

        self::assertSame(
            $document,
            $document->setFileName('stored.pdf')
        );

        self::assertSame(
            $document,
            $document->setPath('/uploads/test.pdf')
        );

        self::assertSame(
            $document,
            $document->setStatus(
                DossierDocumentStatus::UPLOADED
            )
        );
    }
}
