<?php

namespace App\Repository;

use App\Entity\Brand;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\VehicleModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleModel>
 */
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
            ->orderBy('vm.brand', 'ASC')
            ->getQuery();
    }

    /**
     * Exemple : trouver un modèle par marque + modèle + variante
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
}
