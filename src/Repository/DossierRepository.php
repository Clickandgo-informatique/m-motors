<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', $status)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

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

    /**
     * Normalisation stricte du terme de recherche
     */
    private function normalizeSearchTerm(mixed $searchTerm): ?string
    {
        if (!is_string($searchTerm)) {
            return null;
        }

        $searchTerm = trim($searchTerm);

        if ($searchTerm === '') {
            return null;
        }

        return mb_strtolower($searchTerm);
    }

    /**
     * Build la requête de recherche de base.
     */
    private function getSearchQueryBuilder(?string $searchTerm = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.customer', 'c')
            ->leftJoin('d.vehicle', 'v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.model', 'm')
            ->addSelect('c', 'v', 'vm', 'm');

        $searchTerm = $this->normalizeSearchTerm($searchTerm);

        if ($searchTerm !== null) {
            $term = '%' . $searchTerm . '%';

            $qb->andWhere('
                LOWER(d.dossierCode) LIKE :term
                OR LOWER(c.lastName) LIKE :term
                OR LOWER(c.firstName) LIKE :term
                OR LOWER(v.vin) LIKE :term
                OR LOWER(v.registrationNumber) LIKE :term
                OR LOWER(m.name) LIKE :term
            ')
                ->setParameter('term', $term);
        }

        return $qb->orderBy('d.createdAt', 'DESC');
    }

    public function searchForPaginator(?string $searchTerm = null): QueryBuilder
    {
        $searchTerm = $this->normalizeSearchTerm($searchTerm);

        return $this->getSearchQueryBuilder($searchTerm);
    }

    public function findForAutocomplete(?string $searchTerm = null): array
    {
        $searchTerm = $this->normalizeSearchTerm($searchTerm);

        $qb = $this->getSearchQueryBuilder($searchTerm);

        return $qb
            ->select([
                'd.id AS id',
                'd.dossierCode AS dossierCode',
            ])
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }
}
