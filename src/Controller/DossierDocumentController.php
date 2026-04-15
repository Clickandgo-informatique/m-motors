<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use App\Enum\DossierDocumentType;
use App\Repository\DossierDocumentRepository;
use App\Service\DossierUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dossier/document')]
class DossierDocumentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private DossierUploadService $uploadService
    ) {}

    // =========================================================
    // UPLOAD DOCUMENTS (DROPZONE)
    // =========================================================

    #[Route('/{id}/upload', name: 'dossier_document_upload', methods: ['POST'])]
    public function upload(Dossier $dossier, Request $request): JsonResponse
    {
        $files = $request->files->all()['file'] ?? null;

        if (!$files) {
            return new JsonResponse(['error' => 'No files uploaded'], 400);
        }

        if (!is_array($files)) {
            $files = [$files];
        }

        $results = [];

        foreach ($files as $file) {

            $upload = $this->uploadService->upload(
                $file,
                'dossiers/' . $dossier->getId() . '/documents'
            );

            $document = new DossierDocument();
            $document->setDossier($dossier);
            $document->setFilename($upload['filename']);
            $document->setPath($upload['path']);
            $document->setOriginalName($upload['originalName']);
            $document->setDocumentType(DossierDocumentType::UPLOAD);

            $this->em->persist($document);

            $results[] = [
                'entity' => $document,
                'jpg' => $upload['filename'],
                'jpg_thumb' => $upload['filename'],
                'path' => $upload['path'],
            ];
        }

        $this->em->flush();

        // reconstruction propre avec IDs après flush
        foreach ($results as $i => $result) {
            /** @var DossierDocument $entity */
            $entity = $result['entity'];

            $results[$i] = [
                'id' => $entity->getId(),
                'jpg' => $result['jpg'],
                'jpg_thumb' => $result['jpg_thumb'],
                'path' => $result['path'],
            ];
        }

        return new JsonResponse([
            'urls' => $results
        ]);
    }

    // =========================================================
    // DELETE DOCUMENT
    // =========================================================

    #[Route('/{id}/delete', name: 'dossier_document_delete', methods: ['POST'])]
    public function delete(
        Dossier $dossier,
        Request $request,
        DossierDocumentRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {

        $filename = $request->request->get('filename');

        if (!$filename) {
            return new JsonResponse(['error' => 'Missing filename'], 400);
        }

        $document = $repo->findOneBy([
            'dossier' => $dossier,
            'filename' => $filename
        ]);

        if (!$document) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $em->remove($document);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
