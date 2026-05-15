<?php

namespace App\Repository;

use App\Entity\Financing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Financing>
 */
class FinancingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Financing::class);
    }

    /**
     * Retourne tous les financements en attente de décision
     *
     * @return Financing[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les financements liés à un dossier
     */
    public function findByDossier(int $dossierId): ?Financing
    {
        return $this->createQueryBuilder('f')
            ->join('f.dossier', 'd')
            ->andWhere('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les financements approuvés
     *
     * @return Financing[]
     */
    public function findApproved(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.status = :status')
            ->setParameter('status', 'approved')
            ->orderBy('f.decidedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les financements refusés
     *
     * @return Financing[]
     */
    public function findRejected(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.status = :status')
            ->setParameter('status', 'rejected')
            ->orderBy('f.decidedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
