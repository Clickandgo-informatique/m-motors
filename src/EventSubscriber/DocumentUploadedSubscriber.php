<?php

namespace App\EventSubscriber;

use App\Event\DocumentUploadedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Réagit à l’upload de document dans un dossier
 * Logique métier découplée du workflow et du service d’upload
 */
class DocumentUploadedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentUploadedEvent::class => 'onDocumentUploaded',
        ];
    }

    /**
     * Traitement après upload d’un document
     */
    public function onDocumentUploaded(DocumentUploadedEvent $event): void
    {
        $document = $event->getDocument();
        $dossier = $event->getDossier();

        // 1. Log métier
        $this->logger->info('Document uploaded', [
            'dossier_id' => $dossier->getId(),
            'document_id' => $document->getId(),
        ]);

        // 2. Exemple logique métier


        if ($dossier->getStatus() === 'pending') {
            $this->logger->info('Dossier still pending after upload');
        }

        // 3. Extension future :
        // - notification email
        // - recalcul statut dossier
        // - validation automatique
    }
}
