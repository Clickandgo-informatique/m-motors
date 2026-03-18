<?php

namespace App\Repository;

use App\Entity\BodyType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BodyType>
 */
class BodyTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BodyType::class);
    }

    //Liste de tous les types de carrosserie
    public function getBodyTypes(): array
    {
        return $this->createQueryBuilder('bt')
            ->orderBy('bt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
