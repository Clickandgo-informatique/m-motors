<?php

namespace App\Repository;

use App\Entity\FeatureCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeatureCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureCategory::class);
    }

    /**
     * 🔎 Trouver une catégorie par son code
     */
    public function findOneByCode(string $code): ?FeatureCategory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->setParameter('code', strtolower($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 📊 Récupérer toutes les catégories triées par position (ou label fallback)
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 🚀 Récupérer toutes les catégories AVEC leurs features (optimisé)
     */
    public function findAllWithFeatures(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.features', 'f')
            ->addSelect('f')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.label', 'ASC')
            ->addOrderBy('f.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 📦 Récupérer uniquement les catégories qui ont au moins une feature
     */
    public function findWithFeaturesOnly(): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.features', 'f')
            ->addSelect('f')
            ->groupBy('c.id')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 🔢 Compter le nombre de features par catégorie
     */
    public function countFeaturesByCategory(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id, c.label, COUNT(f.id) as featureCount')
            ->leftJoin('c.features', 'f')
            ->groupBy('c.id')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}