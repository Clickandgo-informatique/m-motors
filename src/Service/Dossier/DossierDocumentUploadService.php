<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DossierDocumentUploadService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DossierDocumentTypeResolver $typeResolver,
        private EventDispatcherInterface $dispatcher,
        private string $uploadDir,
        private DossierAuditService $auditService
    ) {}

    // =========================================================
    // UPLOAD DOCUMENTS
    // =========================================================

    public function upload(Dossier $dossier, array $files): void
    {
        foreach ($files as $file) {

            if (!$file instanceof UploadedFile) {
                continue;
            }

            // =========================================================
            // FILE INFO
            // =========================================================

            $originalName = $file->getClientOriginalName();
            $extension = $file->guessExtension() ?? 'bin';

            $fileName = uniqid('doc_', true) . '.' . $extension;

            $file->move($this->uploadDir, $fileName);

            // =========================================================
            // ENTITY
            // =========================================================

            $document = new DossierDocument();
            $document->setDossier($dossier);
            $document->setOriginalName($originalName);
            $document->setFileName($fileName);
            $document->setPath('uploads/' . $fileName);

            $document->setDocumentType(
                $this->typeResolver->resolve($dossier)
            );

            $this->em->persist($document);

            // =========================================================
            // AUDIT (DOSSIER GLOBAL)
            // =========================================================

            $this->auditService->log(
                $dossier,
                'document_uploaded',
                null,
                sprintf('Upload fichier: %s', $originalName),
                $document
            );
        }

        $this->em->flush();
    }
}
