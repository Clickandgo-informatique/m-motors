<?php

namespace App\Controller\Admin;

use App\Entity\Dossier;
use App\Repository\DossierWorkflowLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller admin pour consulter l’historique des transitions workflow d’un dossier.
 *
 * Utilisé pour :
 * - debug métier
 * - audit
 * - timeline UI
 */
class DossierWorkflowLogController extends AbstractController
{
    public function __construct(
        private DossierWorkflowLogRepository $repository
    ) {}

    /**
     * Retourne l’historique complet d’un dossier.
     */
    #[Route('/admin/dossier/{id}/workflow-logs', name: 'admin_dossier_workflow_logs', methods: ['GET'])]
    public function list(Dossier $dossier): JsonResponse
    {
        $logs = $this->repository->findByDossier($dossier->getId());

        return $this->json([
            'dossierId' => $dossier->getId(),
            'status' => $dossier->getStatus(),
            'logs' => array_map(static function ($log) {
                return [
                    'id' => $log->getId(),
                    'transition' => $log->getTransition(),
                    'fromStatus' => $log->getFromStatus(),
                    'toStatus' => $log->getToStatus(),
                    'userId' => $log->getUserId(),
                    'createdAt' => $log->getCreatedAt()->format('d/m/Y H:i:s'),
                ];
            }, $logs),
        ]);
    }
}