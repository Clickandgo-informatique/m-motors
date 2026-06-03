<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Event\DocumentUploadedEvent;
use App\EventSubscriber\DocumentUploadedSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DocumentUploadedSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        // vérifie que le subscriber écoute bien le bon événement

        $this->assertSame(
            [
                DocumentUploadedEvent::class => 'onDocumentUploaded',
            ],
            DocumentUploadedSubscriber::getSubscribedEvents()
        );
    }

    public function testOnDocumentUploadedLogsMessages(): void
    {
        // vérifie les logs émis lors de l'upload d'un document

        $document = $this->createMock(DossierDocument::class);
        $document->method('getId')->willReturn(10);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getId')->willReturn(5);
        $dossier->method('getStatus')->willReturn('pending');

        $event = $this->createMock(DocumentUploadedEvent::class);
        $event->method('getDocument')->willReturn($document);
        $event->method('getDossier')->willReturn($dossier);

        $messages = [];

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context = []) use (&$messages): void {
                $messages[] = [
                    'message' => $message,
                    'context' => $context,
                ];
            });

        $subscriber = new DocumentUploadedSubscriber($logger);

        $subscriber->onDocumentUploaded($event);

        $this->assertSame(
            [
                [
                    'message' => 'Document uploaded',
                    'context' => [
                        'dossier_id' => 5,
                        'document_id' => 10,
                    ],
                ],
                [
                    'message' => 'Dossier still pending after upload',
                    'context' => [],
                ],
            ],
            $messages
        );
    }

    public function testOnDocumentUploadedWithoutPendingDossier(): void
    {
        // vérifie le comportement quand le dossier n'est pas en attente

        $document = $this->createMock(DossierDocument::class);
        $document->method('getId')->willReturn(10);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getId')->willReturn(5);
        $dossier->method('getStatus')->willReturn('validated');

        $event = $this->createMock(DocumentUploadedEvent::class);
        $event->method('getDocument')->willReturn($document);
        $event->method('getDossier')->willReturn($dossier);

        $messages = [];

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message, array $context = []) use (&$messages): void {
                $messages[] = [
                    'message' => $message,
                    'context' => $context,
                ];
            });

        $subscriber = new DocumentUploadedSubscriber($logger);

        $subscriber->onDocumentUploaded($event);

        $this->assertSame(
            [
                [
                    'message' => 'Document uploaded',
                    'context' => [
                        'dossier_id' => 5,
                        'document_id' => 10,
                    ],
                ],
            ],
            $messages
        );
    }
}
