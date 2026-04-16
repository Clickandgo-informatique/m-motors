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
    // UPLOAD
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
        // NORMALISATION RÉFÉRENCE DOSSIER
        // =========================================================

        $reference = $dossier->getReference()
            ?? 'dossier-' . $dossier->getId();

        $safeReference = $slugger->slugify($reference);

        $folderBase = sprintf(
            'dossiers/%s/documents',
            $safeReference
        );

        $results = [];

        foreach ($files as $file) {

            if (!$file) {
                continue;
            }

            $upload = $uploadService->upload($file, $folderBase);

            $document = new DossierDocument();
            $document->setDossier($dossier);
            $document->setFileName($upload['filename']);
            $document->setPath($upload['path']);
            $document->setOriginalName($upload['originalName']);
            $document->setDocumentType(DossierDocumentType::UPLOAD);

            $em->persist($document);

            $results[] = $document;
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'folder' => $folderBase,
            'documents' => array_map(static function (DossierDocument $d) {
                return [
                    'id' => $d->getId(),
                    'fileName' => $d->getFileName(),
                    'path' => $d->getPath(),
                ];
            }, $results)
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

        $id = $document->getId();

        $uploadService->deleteEntity($document, $em);

        return new JsonResponse([
            'success' => true,
            'id' => $id
        ]);
    }

    // =========================================================
    // REPLACE FILE
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

        // =========================================================
        // NORMALISATION RÉFÉRENCE DOSSIER
        // =========================================================

        $reference = $dossier->getReference()
            ?? 'dossier-' . $dossier->getId();

        $safeReference = $slugger->slugify($reference);

        $folder = sprintf(
            'dossiers/%s/documents',
            $safeReference
        );

        // upload nouveau fichier
        $upload = $uploadService->upload($file, $folder);

        // suppression ancien fichier
        $uploadService->safeDelete($document->getPath());

        // update entity
        $document->setFileName($upload['filename']);
        $document->setPath($upload['path']);
        $document->setOriginalName($upload['originalName']);

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'folder' => $folder,
            'document' => [
                'id' => $document->getId(),
                'fileName' => $document->getFileName(),
                'path' => $document->getPath(),
            ]
        ]);
    }
}
