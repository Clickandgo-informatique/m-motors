<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * Vérifie si un véhicule est favori d'un utilisateur
     */
    public function isFavorite(User $user, Vehicle $vehicle): bool
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->andWhere('f.vehicle = :vehicle')
            ->setParameter('user', $user)
            ->setParameter('vehicle', $vehicle)
            ->setMaxResults(1);

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Ajoute ou retire un favori
     * @return bool true si ajouté, false si retiré
     */
    public function toggleFavorite(User $user, Vehicle $vehicle): bool
    {
    
        $em = $this->getEntityManager();

        $favorite = $this->findOneBy([
            'user' => $user,
            'vehicle' => $vehicle,
        ]);

        if ($favorite) {
            $em->remove($favorite);
            $em->flush();
            return false;
        }

        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setVehicle($vehicle);
     

        $em->persist($favorite);
        $em->flush();

     

        return true;
    }
}
