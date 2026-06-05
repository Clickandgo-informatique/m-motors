<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierAudit;
use App\Entity\DossierDocument;
use App\Entity\User;
use App\Service\Dossier\DossierAuditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DossierAuditServiceTest extends TestCase
{
    public function testLogCreatesAndPersistsAudit(): void
    {
        // vérifie la création complète d'un audit

        $dossier = new Dossier();
        $user = new User();
        $document = new DossierDocument();

        $persistedAudit = null;

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function (mixed $audit) use (
                        &$persistedAudit,
                        $dossier,
                        $user,
                        $document
                    ): bool {
                        $persistedAudit = $audit;

                        $this->assertInstanceOf(
                            DossierAudit::class,
                            $audit
                        );

                        $this->assertSame(
                            $dossier,
                            $audit->getDossier()
                        );

                        $this->assertSame(
                            'created',
                            $audit->getAction()
                        );

                        $this->assertSame(
                            $user,
                            $audit->getUser()
                        );

                        $this->assertSame(
                            'message de test',
                            $audit->getMessage()
                        );

                        $this->assertSame(
                            $document,
                            $audit->getDocument()
                        );

                        return true;
                    }
                )
            );

        $em->expects($this->once())
            ->method('flush');

        $service = new DossierAuditService($em);

        $service->log(
            $dossier,
            'created',
            $user,
            'message de test',
            $document
        );

        $this->assertInstanceOf(
            DossierAudit::class,
            $persistedAudit
        );
    }

    public function testLogAcceptsNullOptionalArguments(): void
    {
        // vérifie le fonctionnement avec les paramètres optionnels à null

        $dossier = new Dossier();

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function (mixed $audit) use ($dossier): bool {
                        $this->assertInstanceOf(
                            DossierAudit::class,
                            $audit
                        );

                        $this->assertSame(
                            $dossier,
                            $audit->getDossier()
                        );

                        $this->assertSame(
                            'updated',
                            $audit->getAction()
                        );

                        $this->assertNull(
                            $audit->getUser()
                        );

                        $this->assertNull(
                            $audit->getMessage()
                        );

                        $this->assertNull(
                            $audit->getDocument()
                        );

                        return true;
                    }
                )
            );

        $em->expects($this->once())
            ->method('flush');

        $service = new DossierAuditService($em);

        $service->log(
            $dossier,
            'updated'
        );
    }
}