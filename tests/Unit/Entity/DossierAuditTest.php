<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Dossier;
use App\Entity\DossierAudit;
use App\Entity\DossierDocument;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class DossierAuditTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $audit = new DossierAudit();

        self::assertNull($audit->getId());
        self::assertNull($audit->getDossier());
        self::assertNull($audit->getUser());
        self::assertNull($audit->getMessage());
        self::assertNull($audit->getDocument());
    }

    // vérifie l'association au dossier
    public function testDossier(): void
    {
        $audit = new DossierAudit();
        $dossier = new Dossier();

        $audit->setDossier($dossier);

        self::assertSame(
            $dossier,
            $audit->getDossier()
        );
    }

    // vérifie l'utilisateur
    public function testUser(): void
    {
        $audit = new DossierAudit();
        $user = new User();

        $audit->setUser($user);

        self::assertSame(
            $user,
            $audit->getUser()
        );
    }

    // vérifie qu'un utilisateur peut être null
    public function testUserCanBeNull(): void
    {
        $audit = new DossierAudit();

        $audit->setUser(null);

        self::assertNull($audit->getUser());
    }

    // vérifie l'action
    public function testAction(): void
    {
        $audit = new DossierAudit();

        $audit->setAction('document_uploaded');

        self::assertSame(
            'document_uploaded',
            $audit->getAction()
        );
    }

    // vérifie le message
    public function testMessage(): void
    {
        $audit = new DossierAudit();

        $audit->setMessage('document ajouté');

        self::assertSame(
            'document ajouté',
            $audit->getMessage()
        );
    }

    // vérifie qu'un message peut être null
    public function testMessageCanBeNull(): void
    {
        $audit = new DossierAudit();

        $audit->setMessage(null);

        self::assertNull($audit->getMessage());
    }

    // vérifie le document lié
    public function testDocument(): void
    {
        $audit = new DossierAudit();
        $document = new DossierDocument();

        $audit->setDocument($document);

        self::assertSame(
            $document,
            $audit->getDocument()
        );
    }

    // vérifie qu'un document peut être null
    public function testDocumentCanBeNull(): void
    {
        $audit = new DossierAudit();

        $audit->setDocument(null);

        self::assertNull($audit->getDocument());
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $audit = new DossierAudit();

        self::assertSame(
            $audit,
            $audit->setAction('test')
        );

        self::assertSame(
            $audit,
            $audit->setMessage('message')
        );

        self::assertSame(
            $audit,
            $audit->setUser(null)
        );

        self::assertSame(
            $audit,
            $audit->setDocument(null)
        );
    }
}
