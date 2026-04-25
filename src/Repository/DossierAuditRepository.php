<?php

namespace App\Repository;

use App\Entity\DossierAudit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des logs d'audit liés aux dossiers.
 *
 * Permet de :
 * - tracer l'historique complet d'un dossier
 * - filtrer par action (upload, workflow, etc.)
 * - construire une timeline métier
 */
class DossierAuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierAudit::class);
    }

    // =========================================================
    // TIMELINE DOSSIER
    // =========================================================

    public function findByDossierOrdered(int $dossierId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.dossier = :id')
            ->setParameter('id', $dossierId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // =========================================================
    // FILTRER PAR ACTION
    // =========================================================

    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // =========================================================
    // DERNIERES ACTIONS
    // =========================================================

    public function findLatestByDossier(int $dossierId, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.dossier = :id')
            ->setParameter('id', $dossierId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
