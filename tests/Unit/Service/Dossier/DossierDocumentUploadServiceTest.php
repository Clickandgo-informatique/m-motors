<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Enum\DossierDocumentType;
use App\Service\Dossier\DossierAuditService;
use App\Service\Dossier\DossierDocumentTypeResolver;
use App\Service\Dossier\DossierDocumentUploadService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DossierDocumentUploadServiceTest extends TestCase
{
    public function testUploadPersistsDocumentAndCreatesAudit(): void
    {
        // vérifie l'upload complet d'un document

        $dossier = new Dossier();

        $resolver = $this->createMock(DossierDocumentTypeResolver::class);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with($dossier)
            ->willReturn(DossierDocumentType::UPLOAD);

        $auditService = $this->createMock(DossierAuditService::class);

        $auditService->expects($this->once())
            ->method('log')
            ->with(
                $dossier,
                'document_uploaded',
                null,
                $this->stringContains('contrat.pdf'),
                $this->isInstanceOf(DossierDocument::class)
            );

        $uploadedFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getClientOriginalName',
                'guessExtension',
                'move',
            ])
            ->getMock();

        $uploadedFile->method('getClientOriginalName')
            ->willReturn('contrat.pdf');

        $uploadedFile->method('guessExtension')
            ->willReturn('pdf');

        $uploadedFile->expects($this->once())
            ->method('move')
            ->with('/tmp/uploads', $this->stringEndsWith('.pdf'));

        $persistedDocument = null;

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function (mixed $document) use (
                        &$persistedDocument,
                        $dossier
                    ): bool {
                        $persistedDocument = $document;

                        $this->assertInstanceOf(
                            DossierDocument::class,
                            $document
                        );

                        $this->assertSame(
                            $dossier,
                            $document->getDossier()
                        );

                        $this->assertSame(
                            'contrat.pdf',
                            $document->getOriginalName()
                        );

                        $this->assertSame(
                            DossierDocumentType::UPLOAD,
                            $document->getDocumentType()
                        );

                        $this->assertStringStartsWith(
                            'uploads/doc_',
                            $document->getPath()
                        );

                        return true;
                    }
                )
            );

        $em->expects($this->once())
            ->method('flush');

        $service = new DossierDocumentUploadService(
            $em,
            $resolver,
            $this->createMock(EventDispatcherInterface::class),
            '/tmp/uploads',
            $auditService
        );

        $service->upload(
            $dossier,
            [$uploadedFile]
        );

        $this->assertInstanceOf(
            DossierDocument::class,
            $persistedDocument
        );
    }

    public function testUploadIgnoresInvalidEntries(): void
    {
        // vérifie que seuls les UploadedFile sont traités

        $dossier = new Dossier();

        $resolver = $this->createMock(DossierDocumentTypeResolver::class);

        $resolver->expects($this->never())
            ->method('resolve');

        $auditService = $this->createMock(DossierAuditService::class);

        $auditService->expects($this->never())
            ->method('log');

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->never())
            ->method('persist');

        $em->expects($this->once())
            ->method('flush');

        $service = new DossierDocumentUploadService(
            $em,
            $resolver,
            $this->createMock(EventDispatcherInterface::class),
            '/tmp/uploads',
            $auditService
        );

        $service->upload(
            $dossier,
            [
                'invalid',
                123,
                new \stdClass(),
            ]
        );
    }

    public function testUploadUsesBinExtensionWhenGuessExtensionReturnsNull(): void
    {
        // vérifie l'utilisation de l'extension bin par défaut

        $dossier = new Dossier();

        $resolver = $this->createMock(DossierDocumentTypeResolver::class);

        $resolver->method('resolve')
            ->willReturn(DossierDocumentType::UPLOAD);

        $auditService = $this->createMock(DossierAuditService::class);

        $auditService->expects($this->once())
            ->method('log');

        $uploadedFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getClientOriginalName',
                'guessExtension',
                'move',
            ])
            ->getMock();

        $uploadedFile->method('getClientOriginalName')
            ->willReturn('document');

        $uploadedFile->method('guessExtension')
            ->willReturn(null);

        $uploadedFile->expects($this->once())
            ->method('move')
            ->with(
                '/tmp/uploads',
                $this->stringEndsWith('.bin')
            );

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist');

        $em->expects($this->once())
            ->method('flush');

        $service = new DossierDocumentUploadService(
            $em,
            $resolver,
            $this->createMock(EventDispatcherInterface::class),
            '/tmp/uploads',
            $auditService
        );

        $service->upload(
            $dossier,
            [$uploadedFile]
        );
    }
}
