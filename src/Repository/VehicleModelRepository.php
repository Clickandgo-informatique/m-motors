<?php

namespace App\Repository;

use App\Entity\Brand;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\VehicleModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VehicleModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleModel::class);
    }

    /**
     * Charge tous les VehicleModel avec leurs relations
     */
    public function findAllWithRelations()
    {
        return $this->createQueryBuilder('vm')
            ->leftJoin('vm.brand', 'b')->addSelect('b')
            ->leftJoin('vm.model', 'm')->addSelect('m')
            ->leftJoin('vm.variant', 'v')->addSelect('v')
            ->leftJoin('vm.fuelType', 'f')->addSelect('f')
            ->leftJoin('vm.gear', 'g')->addSelect('g')
            ->orderBy('b.name', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->addOrderBy('v.name', 'ASC')
            ->getQuery();
    }

    /**
     * Trouver un modèle par marque + modèle + variante
     */
    public function findOneBySignature(Brand $brand, Model $model, Variant $variant): ?VehicleModel
    {
        return $this->createQueryBuilder('vm')
            ->andWhere('vm.brand = :brand')
            ->andWhere('vm.model = :model')
            ->andWhere('vm.variant = :variant')
            ->setParameter('brand', $brand)
            ->setParameter('model', $model)
            ->setParameter('variant', $variant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte total des résultats (pour pagination)
     */
    public function countSearch(string $term): int
    {
        if (trim($term) === '') {
            return 0;
        }

        return (int) $this->createQueryBuilder('vm')
            ->select('COUNT(vm.id)')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.variant', 'v')
            ->andWhere('LOWER(b.name) LIKE :term 
                     OR LOWER(m.name) LIKE :term 
                     OR LOWER(v.name) LIKE :term')
            ->setParameter('term', '%' . strtolower($term) . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche paginée (scroll infini)
     */
    public function searchPaginated(string $term, int $limit, int $offset): array
    {
        if (trim($term) === '') {
            return [];
        }

        $qb = $this->createQueryBuilder('vm')
            ->select('vm.id, b.name AS brand_name, m.name AS model_name, v.name AS variant_name')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.variant', 'v')
            ->andWhere('
            LOWER(b.name) LIKE :contains
            OR LOWER(m.name) LIKE :contains
            OR LOWER(v.name) LIKE :contains
        ')
            ->setParameter('contains', '%' . strtolower($term) . '%')
            ->orderBy('
            CASE 
                WHEN LOWER(b.name) LIKE :starts THEN 0
                WHEN LOWER(m.name) LIKE :starts THEN 0
                WHEN LOWER(v.name) LIKE :starts THEN 0
                ELSE 1
            END
        ', 'ASC')
            ->addOrderBy('b.name', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->addOrderBy('v.name', 'ASC')
            ->setParameter('starts', strtolower($term) . '%')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getArrayResult();
    }
}
