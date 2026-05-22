<?php

namespace App\Repository;

use App\Entity\VehicleBadge;
use App\Enum\VehicleBadgeCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleBadge>
 */
class VehicleBadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleBadge::class);
    }

    /**
     * Retourne tous les badges actifs triés par catégorie puis priorité
     *
     * @return VehicleBadge[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('b.category', 'ASC')
            ->addOrderBy('b.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les badges actifs d'une catégorie donnée
     *
     * @return VehicleBadge[]
     */
    public function findByCategory(VehicleBadgeCategory $category): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.category = :category')
            ->andWhere('b.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('b.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un badge par code (usage fixtures / services auto)
     */
    public function findOneByCode(string $code): ?VehicleBadge
    {
        return $this->createQueryBuilder('b')
            ->andWhere('LOWER(b.code) = LOWER(:code)')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne uniquement les badges "marketing" (commercial + audience)
     * utile pour la galerie publique
     *
     * @return VehicleBadge[]
     */
    public function findPublicBadges(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = :active')
            ->andWhere('b.category IN (:categories)')
            ->setParameter('active', true)
            ->setParameter('categories', [
                VehicleBadgeCategory::COMMERCIAL,
                VehicleBadgeCategory::AUDIENCE,
                VehicleBadgeCategory::STATE,
                VehicleBadgeCategory::ECOLOGY,
            ])
            ->orderBy('b.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }
}