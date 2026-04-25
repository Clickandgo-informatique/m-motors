<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Enum\DossierDocumentStatus;
use App\Enum\DossierDocumentType;
use App\Service\DossierUploadService;
use App\Service\DossierWorkflowService;
use App\Service\Utils\SluggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DossierDocumentController extends AbstractController
{
    public function __construct(
        private DossierWorkflowService $dossierWorkflowService
    ) {}
    // =========================================================
    // UPLOAD
    // =========================================================
    #[Route('/dossier/{id}/upload', name: 'dossier_document_upload', methods: ['POST'])]
    public function upload(
        Dossier $dossier,
        Request $request,
        DossierUploadService $uploadService,
        SluggerService $slugger,
        DossierWorkflowService $workflow,
        EntityManagerInterface $em
    ): JsonResponse {

        if ($dossier->getStatus() === 'validated') {
            return new JsonResponse(['error' => 'Dossier verrouillé'], 403);
        }

        $files = $request->files->get('file');

        if (!$files) {
            return new JsonResponse(['error' => 'No files uploaded'], 400);
        }

        if (!is_array($files)) {
            $files = [$files];
        }

        $reference = $dossier->getDossierCode() ?? 'dossier-' . $dossier->getId();
        $safeReference = $slugger->slugify($reference);

        $folderBase = sprintf('dossiers/%s/documents', $safeReference);

        foreach ($files as $file) {

            if (!$file) {
                continue;
            }

            $existing = $em->getRepository(DossierDocument::class)
                ->findOneBy([
                    'dossier' => $dossier,
                    'originalName' => $file->getClientOriginalName(),
                ]);

            if ($existing) {
                continue;
            }

            $upload = $uploadService->upload($file, $folderBase);

            $document = new DossierDocument();
            $document->setDossier($dossier);
            $document->setFileName($upload['filename']);
            $document->setPath($upload['path']);
            $document->setOriginalName($upload['originalName']);
            $document->setDocumentType(DossierDocumentType::UPLOAD);
            $document->setStatus(DossierDocumentStatus::UPLOADED);

            $em->persist($document);
        }

        $em->flush();

        $workflow->refreshDossierStatus($dossier);

        return $this->json([
            'success' => true,
            'documents' => $this->serializeDocuments($dossier),
        ]);
    }

    // =========================================================
    // DELETE
    // =========================================================
    #[Route('/document/{id}', name: 'dossier_document_delete', methods: ['DELETE'])]
    public function delete(
        DossierDocument $document,
        DossierUploadService $uploadService,
        EntityManagerInterface $em
    ): JsonResponse {

        // 🔒 Bloque suppression si statut final
        if ($document->getStatus()->isFinal()) {
            return new JsonResponse(['error' => 'Document verrouillé'], 403);
        }

        $dossier = $document->getDossier();

        $uploadService->deleteEntity($document, $em);

        return $this->json([
            'success' => true,
            'id' => $document->getId(),
            'documents' => $this->serializeDocuments($dossier),
        ]);
    }

    // =========================================================
    // REPLACE
    // =========================================================
    #[Route('/document/{id}/replace', name: 'dossier_document_replace', methods: ['POST'])]
    public function replace(
        DossierDocument $document,
        Request $request,
        DossierUploadService $uploadService,
        SluggerService $slugger,
        EntityManagerInterface $em
    ): JsonResponse {

        // Bloque modification si document validé/refusé
        if ($document->getStatus()->isFinal()) {
            return new JsonResponse(['error' => 'Document verrouillé'], 403);
        }

        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'No file provided'], 400);
        }

        $dossier = $document->getDossier();

        $reference = $dossier->getDossierCode()
            ?? 'dossier-' . $dossier->getId();

        $safeReference = $slugger->slugify($reference);

        $folder = sprintf(
            'dossiers/%s/documents',
            $safeReference
        );

        $upload = $uploadService->upload($file, $folder);

        // suppression ancien fichier physique
        $uploadService->safeDelete($document->getPath());

        // mise à jour
        $document->setFileName($upload['filename']);
        $document->setPath($upload['path']);
        $document->setOriginalName($upload['originalName']);

        // reset statut (important)
        $document->setStatus(DossierDocumentStatus::UPLOADED);

        $em->flush();

        return $this->json([
            'success' => true,
            'document' => [
                'id' => $document->getId(),
                'fileName' => $document->getFileName(),
                'path' => $document->getPath(),
                'createdAt' => $document->getCreatedAt()?->format('d/m/Y H:i'),
                'status' => $document->getStatus()->value,
                'statusLabel' => $document->getStatus()->label(),
                'badge' => $document->getStatus()->badge(),
            ],
            'documents' => $this->serializeDocuments($dossier),
        ]);
    }

    // =========================================================
    // LIST
    // =========================================================
    #[Route('/dossier/{id}/documents', name: 'dossier_documents_list', methods: ['GET'])]
    public function list(Dossier $dossier): JsonResponse
    {
        return $this->json([
            'documents' => $this->serializeDocuments($dossier),
        ]);
    }

    // =========================================================
    // SERIALIZER
    // =========================================================
    private function serializeDocuments(Dossier $dossier): array
    {
        $completionRate = $this->dossierWorkflowService->getCompletionRate($dossier);

        return array_map(static function (DossierDocument $doc) use ($completionRate) {
            return [
                'id' => $doc->getId(),
                'fileName' => $doc->getFileName(),
                'path' => $doc->getPath(),
                'createdAt' => $doc->getCreatedAt()?->format('d/m/Y H:i'),
                'status' => $doc->getStatus()->value,
                'statusLabel' => $doc->getStatus()->label(),
                'badge' => $doc->getStatus()->badge(),
                'type' => $doc->getDocumentType()->value,
                'completionRate' => $completionRate,
            ];
        }, $dossier->getDocuments()->toArray());
    }
}
