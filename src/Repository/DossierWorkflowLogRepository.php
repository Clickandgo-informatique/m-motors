<?php

namespace App\Repository;

use App\Entity\DossierWorkflowLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des logs de workflow Dossier.
 *
 * Permet :
 * - récupération de l’historique d’un dossier
 * - analyse des transitions
 * - debug des workflows
 * - construction de timeline UI
 */
class DossierWorkflowLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierWorkflowLog::class);
    }

    /**
     * Retourne l’historique complet d’un dossier (ordre chronologique).
     */
    public function findByDossier(int $dossierId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.dossier = :id')
            ->setParameter('id', $dossierId)
            ->orderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les dernières transitions d’un dossier.
     */
    public function findLatestByDossier(int $dossierId, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.dossier = :id')
            ->setParameter('id', $dossierId)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de transitions par type.
     */
    public function countTransitionsByType(int $dossierId): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.transition, COUNT(l.id) as total')
            ->andWhere('l.dossier = :id')
            ->setParameter('id', $dossierId)
            ->groupBy('l.transition')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retourne la timeline complète optimisée pour affichage UI.
     */
    public function getTimeline(int $dossierId): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.transition, l.fromStatus, l.toStatus, l.createdAt, l.userId')
            ->andWhere('l.dossier = :id')
            ->setParameter('id', $dossierId)
            ->orderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
