<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Dossier;
use App\Entity\DossierWorkflowLog;
use PHPUnit\Framework\TestCase;

class DossierWorkflowLogTest extends TestCase
{
    // vérifie les valeurs par défaut
    public function testDefaultValues(): void
    {
        $before = new \DateTimeImmutable();

        $log = new DossierWorkflowLog();

        $after = new \DateTimeImmutable();

        self::assertNull($log->getId());
        self::assertNull($log->getDossier());
        self::assertNull($log->getUserId());

        self::assertGreaterThanOrEqual(
            $before->getTimestamp(),
            $log->getCreatedAt()->getTimestamp()
        );

        self::assertLessThanOrEqual(
            $after->getTimestamp(),
            $log->getCreatedAt()->getTimestamp()
        );
    }

    // vérifie l'association au dossier
    public function testDossier(): void
    {
        $log = new DossierWorkflowLog();
        $dossier = new Dossier();

        $log->setDossier($dossier);

        self::assertSame(
            $dossier,
            $log->getDossier()
        );
    }

    // vérifie qu'un dossier peut être null
    public function testDossierCanBeNull(): void
    {
        $log = new DossierWorkflowLog();

        $log->setDossier(null);

        self::assertNull($log->getDossier());
    }

    // vérifie la transition
    public function testTransition(): void
    {
        $log = new DossierWorkflowLog();

        $log->setTransition('validate_documents');

        self::assertSame(
            'validate_documents',
            $log->getTransition()
        );
    }

    // vérifie le statut source
    public function testFromStatus(): void
    {
        $log = new DossierWorkflowLog();

        $log->setFromStatus('documents_pending');

        self::assertSame(
            'documents_pending',
            $log->getFromStatus()
        );
    }

    // vérifie le statut cible
    public function testToStatus(): void
    {
        $log = new DossierWorkflowLog();

        $log->setToStatus('documents_review');

        self::assertSame(
            'documents_review',
            $log->getToStatus()
        );
    }

    // vérifie l'identifiant utilisateur
    public function testUserId(): void
    {
        $log = new DossierWorkflowLog();

        $log->setUserId(42);

        self::assertSame(
            42,
            $log->getUserId()
        );
    }

    // vérifie qu'un utilisateur peut être null
    public function testUserIdCanBeNull(): void
    {
        $log = new DossierWorkflowLog();

        $log->setUserId(null);

        self::assertNull($log->getUserId());
    }

    // vérifie la date de création
    public function testCreatedAt(): void
    {
        $log = new DossierWorkflowLog();

        $date = new \DateTimeImmutable('2026-01-15 10:00:00');

        $log->setCreatedAt($date);

        self::assertSame(
            $date,
            $log->getCreatedAt()
        );
    }

    // vérifie le chaînage des setters
    public function testFluentSetters(): void
    {
        $log = new DossierWorkflowLog();

        self::assertSame(
            $log,
            $log->setTransition('transition')
        );

        self::assertSame(
            $log,
            $log->setFromStatus('from')
        );

        self::assertSame(
            $log,
            $log->setToStatus('to')
        );

        self::assertSame(
            $log,
            $log->setUserId(1)
        );

        self::assertSame(
            $log,
            $log->setDossier(new Dossier())
        );
    }

    // vérifie le remplacement de la date créée par défaut
    public function testSetCreatedAtOverridesConstructorValue(): void
    {
        $log = new DossierWorkflowLog();

        $customDate = new \DateTimeImmutable('2025-05-01 08:00:00');

        $log->setCreatedAt($customDate);

        self::assertSame(
            $customDate,
            $log->getCreatedAt()
        );
    }
}
