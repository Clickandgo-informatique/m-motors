<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    /**
     * Récupérer les dossiers d’un client
     */
    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les dossiers par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', $status)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Back-office : dossiers à traiter
     */
    public function findToReview(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status IN (:statuses)')
            ->setParameter('statuses', [
                'submitted',
                'under_review'
            ])
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Dernier dossier d’un customer
     */
    public function findLastByCustomer(Customer $customer): ?Dossier
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('d.dossierCode', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
