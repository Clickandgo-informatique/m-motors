<?php

namespace App\Repository;

use App\Entity\SupplierOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des commandes fournisseurs
 *
 * Permet d'encapsuler toutes les requêtes métier liées aux SupplierOrder
 */
class SupplierOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierOrder::class);
    }

    /**
     * Récupère toutes les commandes triées par date décroissante
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('so')
            ->orderBy('so.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les commandes par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('so')
            ->andWhere('so.status = :status')
            ->setParameter('status', $status)
            ->orderBy('so.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Commandes en cours (non finalisées)
     */
    public function findActiveOrders(): array
    {
        return $this->createQueryBuilder('so')
            ->andWhere('so.status IN (:statuses)')
            ->setParameter('statuses', ['ordered'])
            ->orderBy('so.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
