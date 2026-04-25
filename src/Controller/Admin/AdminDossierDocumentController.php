<?php

namespace App\Controller\Admin;

use App\Entity\DossierDocument;
use App\Enum\DossierDocumentStatus;
use App\Service\DossierWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/document')]
class AdminDossierDocumentController extends AbstractController
{
    #[Route('/{id}/validate', name: 'admin_document_validate', methods: ['POST'])]
    public function validate(
        DossierDocument $document,
        EntityManagerInterface $em,
        DossierWorkflowService $workflow
    ): JsonResponse {

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        if (in_array($document->getStatus(), [
            DossierDocumentStatus::VALIDATED,
            DossierDocumentStatus::REJECTED
        ], true)) {
            return new JsonResponse(['error' => 'Déjà traité'], 400);
        }

        $document->setStatus(DossierDocumentStatus::VALIDATED);

        $workflow->refreshDossierStatus($document->getDossier());

        $em->flush();

        return $this->json([
            'success' => true,
            'status' => $document->getStatus()->value,
            'statusLabel' => $document->getStatus()->label(),
            'badge' => $document->getStatus()->badge(),
        ]);
    }

    #[Route('/{id}/reject', name: 'admin_document_reject', methods: ['POST'])]
    public function reject(
        DossierDocument $document,
        EntityManagerInterface $em,
        DossierWorkflowService $workflow
    ): JsonResponse {

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        if (in_array($document->getStatus(), [
            DossierDocumentStatus::VALIDATED,
            DossierDocumentStatus::REJECTED
        ], true)) {
            return new JsonResponse(['error' => 'Déjà traité'], 400);
        }

        $document->setStatus(DossierDocumentStatus::REJECTED);

        $workflow->refreshDossierStatus($document->getDossier());

        $em->flush();

        return $this->json([
            'success' => true,
            'status' => $document->getStatus()->value,
            'statusLabel' => $document->getStatus()->label(),
            'badge' => $document->getStatus()->badge(),
        ]);
    }
}
