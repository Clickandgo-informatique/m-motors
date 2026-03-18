<?php

namespace App\Repository;

use App\Entity\FuelType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FuelType>
 */
class FuelTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FuelType::class);
    }
    public function getFuelTypes(): array
    {
        return $this->createQueryBuilder('ft')
            ->orderBy('ft.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
