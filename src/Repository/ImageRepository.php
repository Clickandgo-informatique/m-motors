<?php

namespace App\Repository;

use App\Entity\Image;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * Retourne toutes les images d’un véhicule triées par position
     *
     * @return Image[]
     */
    public function findByVehicleOrdered(Vehicle $vehicle): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.vehicle = :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->orderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l’image principale (featured) d’un véhicule
     */
    public function findFeaturedByVehicle(Vehicle $vehicle): ?Image
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.vehicle = :vehicle')
            ->andWhere('i.isFeatured = :featured')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('featured', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Désactive toutes les images featured d’un véhicule
     * (utile avant de définir une nouvelle image principale)
     */
    public function clearFeaturedForVehicle(Vehicle $vehicle): void
    {
        $this->createQueryBuilder('i')
            ->update()
            ->set('i.isFeatured', ':false')
            ->andWhere('i.vehicle = :vehicle')
            ->setParameter('false', false)
            ->setParameter('vehicle', $vehicle)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère la prochaine position disponible pour un véhicule
     */
    public function getNextPosition(Vehicle $vehicle): int
    {
        $max = $this->createQueryBuilder('i')
            ->select('MAX(i.position)')
            ->andWhere('i.vehicle = :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }
}
