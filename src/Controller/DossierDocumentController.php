<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Enum\DossierDocumentType;
use App\Service\DossierUploadService;
use App\Service\Utils\SluggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DossierDocumentController extends AbstractController
{
    // =========================================================
    // UPLOAD (STATE SYNC VERSION)
    // =========================================================
    #[Route('/dossier/{id}/upload', name: 'dossier_document_upload', methods: ['POST'])]
    public function upload(
        Dossier $dossier,
        Request $request,
        DossierUploadService $uploadService,
        SluggerService $slugger,
        EntityManagerInterface $em
    ): JsonResponse {

        $files = $request->files->get('file');

        if (!$files) {
            return new JsonResponse(['error' => 'No files uploaded'], 400);
        }

        if (!is_array($files)) {
            $files = [$files];
        }

        // =========================================================
        // DOSSIER REFERENCE
        // =========================================================
        $reference = $dossier->getDossierCode()
            ?? 'dossier-' . $dossier->getId();

        $safeReference = $slugger->slugify($reference);

        $folderBase = sprintf(
            'dossiers/%s/documents',
            $safeReference
        );

        foreach ($files as $file) {

            if (!$file) {
                continue;
            }

            // =========================================================
            // DUPLICATE CHECK (backend safety)
            // =========================================================
            $existing = $em->getRepository(DossierDocument::class)
                ->findOneBy([
                    'dossier' => $dossier,
                    'originalName' => $file->getClientOriginalName(),
                ]);

            if ($existing) {
                continue;
            }

            // =========================================================
            // UPLOAD FILE
            // =========================================================
            $upload = $uploadService->upload($file, $folderBase);

            $document = new DossierDocument();
            $document->setDossier($dossier);
            $document->setFileName($upload['filename']);
            $document->setPath($upload['path']);
            $document->setOriginalName($upload['originalName']);
            $document->setDocumentType(DossierDocumentType::UPLOAD);

            $em->persist($document);
        }

        $em->flush();

        // =========================================================
        // RETURN FULL STATE (IMPORTANT FIX)
        // =========================================================
        return $this->json([
            'success' => true,
            'folder' => $folderBase,
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

        $uploadService->safeDelete($document->getPath());

        $document->setFileName($upload['filename']);
        $document->setPath($upload['path']);
        $document->setOriginalName($upload['originalName']);

        $em->flush();

        return $this->json([
            'success' => true,
            'document' => [
                'id' => $document->getId(),
                'fileName' => $document->getFileName(),
                'path' => $document->getPath(),
                'createdAt' => $document->getCreatedAt()?->format('d/m/Y H:i'),
            ],
            'documents' => $this->serializeDocuments($dossier),
        ]);
    }

    // =========================================================
    // LIST (SOURCE OF TRUTH)
    // =========================================================
    #[Route('/dossier/{id}/documents', name: 'dossier_documents_list', methods: ['GET'])]
    public function list(Dossier $dossier): JsonResponse
    {
        return $this->json([
            'documents' => $this->serializeDocuments($dossier),
        ]);
    }

    // =========================================================
    // SERIALIZER (CENTRALISÉ)
    // =========================================================
    private function serializeDocuments(Dossier $dossier): array
    {
        return array_map(static function (DossierDocument $doc) {
            return [
                'id' => $doc->getId(),
                'fileName' => $doc->getFileName(),
                'path' => $doc->getPath(),
                'createdAt' => $doc->getCreatedAt()?->format('d/m/Y H:i'),
            ];
        }, $dossier->getDocuments()->toArray());
    }
}
