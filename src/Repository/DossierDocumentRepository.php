<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\DossierDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des documents liés aux dossiers.
 *
 * Permet de centraliser les requêtes métier :
 * - documents par dossier
 * - documents par statut
 * - documents par type
 */
class DossierDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierDocument::class);
    }

    // =========================================================
    // DOCUMENTS D’UN DOSSIER
    // =========================================================

    /**
     * Retourne tous les documents d’un dossier.
     */
    public function findByDossier(Dossier $dossier): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.dossier = :dossier')
            ->setParameter('dossier', $dossier)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // =========================================================
    // DOCUMENTS PAR STATUT
    // =========================================================

    /**
     * Documents en attente de validation.
     */
    public function findPendingByDossier(Dossier $dossier): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.dossier = :dossier')
            ->andWhere('d.status = :status')
            ->setParameter('dossier', $dossier)
            ->setParameter('status', 'pending')
            ->orderBy('d.uploadedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Documents validés.
     */
    public function findApprovedByDossier(Dossier $dossier): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.dossier = :dossier')
            ->andWhere('d.status = :status')
            ->setParameter('dossier', $dossier)
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getResult();
    }

    /**
     * Documents rejetés.
     */
    public function findRejectedByDossier(Dossier $dossier): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.dossier = :dossier')
            ->andWhere('d.status = :status')
            ->setParameter('dossier', $dossier)
            ->setParameter('status', 'rejected')
            ->getQuery()
            ->getResult();
    }

    // =========================================================
    // STATISTIQUE SIMPLE (utile progression)
    // =========================================================

    /**
     * Compte les documents par statut.
     */
    public function countByStatus(Dossier $dossier, string $status): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.dossier = :dossier')
            ->andWhere('d.status = :status')
            ->setParameter('dossier', $dossier)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
