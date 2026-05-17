<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Service\Dossier\DossierDocumentUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DossierDocumentUploadController extends AbstractController
{
    #[Route('/dossier/{id}/documents/upload', name: 'dossier_document_upload', methods: ['POST'])]
    public function upload(
        Dossier $dossier,
        Request $request,
        DossierDocumentUploadService $uploadService
    ): JsonResponse {

        if (in_array($dossier->getStatus(), ['completed', 'cancelled'], true)) {
            return new JsonResponse(['error' => 'Upload interdit'], 403);
        }

        $files = $request->files->get('file');

        if (!$files) {
            return new JsonResponse(['error' => 'Aucun fichier'], 400);
        }

        $uploadService->upload($dossier, $files);

        return new JsonResponse(['success' => true]);
    }
}
