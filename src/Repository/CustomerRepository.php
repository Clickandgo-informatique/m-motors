<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * Recherche simple (autocomplete)
     */
    public function search(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.firstName) LIKE :term OR LOWER(c.lastName) LIKE :term OR LOWER(c.email) LIKE :term')
            ->setParameter('term', '%' . strtolower($term) . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver par email
     */
    public function findOneByEmail(string $email): ?Customer
    {
        return $this->findOneBy(['email' => strtolower($email)]);
    }
    /**
     * Récupère le dernier customer pour un préfixe donné
     */
    public function findLastByPrefix(string $prefix): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->where('c.customerCode LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('c.customerCode', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
