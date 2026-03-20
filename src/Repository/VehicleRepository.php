<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * QueryBuilder pour récupérer tous les véhicules avec jointures
     */
    public function getAllVehiclesQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.brand', 'b')
            ->addSelect('vm', 'm', 'b')
            ->orderBy('v.id', 'DESC');
    }

    /**
     * QueryBuilder pour filtrer dynamiquement
     */
    public function findByFiltersQueryBuilder(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b');

        // Filtre marque
        if (!empty($filters['brand'])) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $filters['brand']);
        }

        // Filtre carburant
        if (!empty($filters['fuel'])) {
            $qb->andWhere('vm.fuelType IN (:fuel)')
                ->setParameter('fuel', $filters['fuel']);
        }

        // Kilométrage
        if (isset($filters['mileageMin'], $filters['mileageMax'])) {
            $qb->andWhere('v.mileage >= :min AND v.mileage <= :max')
                ->setParameter('min', $filters['mileageMin'])
                ->setParameter('max', $filters['mileageMax']);
        }

        // Prix
        if (!empty($filters['priceMin'])) {
            $qb->andWhere('v.price >= :minPrice')
                ->setParameter('minPrice', $filters['priceMin']);
        }
        if (!empty($filters['priceMax'])) {
            $qb->andWhere('v.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['priceMax']);
        }

        return $qb->orderBy('vm.brand', 'ASC');
    }

    /**
     * Liste des marques ayant au moins un véhicule (pour le filtre dynamique)
     */
    public function findBrandNamesWithVehicles(): array
    {
        return $this->createQueryBuilder('v')
            ->select('DISTINCT b.id, b.name')
            ->join('v.vehicleModel', 'm')
            ->join('m.brand', 'b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Recherche paginée ou autocomplete pour les véhicules
     *
     * @param array $filters Tableau des filtres ['brand' => [1,2], 'fuel' => [1,2], 'mileageMin' => 0, ...]
     * @param string|null $searchTerm Terme pour recherche texte
     * @param int|null $limit Nombre maximum de résultats
     * @param int|null $offset Décalage pour pagination
     * @return Vehicle[]
     */
    public function searchPaginated(array $filters = [], ?string $searchTerm = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->addSelect('vm', 'b');

        // 🔹 Filtrage des marques
        if (!empty($filters['brand'])) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $filters['brand']);
        }

        // 🔹 Filtrage carburant
        if (!empty($filters['fuel'])) {
            $qb->andWhere('v.fuelType IN (:fuel)')
                ->setParameter('fuel', $filters['fuel']);
        }

        // 🔹 Filtrage carrosserie
        if (!empty($filters['bodyType'])) {
            $qb->andWhere('v.bodyType IN (:bodyTypes)')
                ->setParameter('bodyTypes', $filters['bodyType']);
        }

        // 🔹 Filtrage sliders (mileage, price, etc.)
        if (isset($filters['mileageMin'])) {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', $filters['mileageMin']);
        }
        if (isset($filters['mileageMax'])) {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', $filters['mileageMax']);
        }

        if (isset($filters['priceMin'])) {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', $filters['priceMin']);
        }
        if (isset($filters['priceMax'])) {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', $filters['priceMax']);
        }

        // 🔹 Recherche texte (marque, modèle, immatriculation, VIN)
        if ($searchTerm) {
            $qb->andWhere('
                LOWER(v.registrationNumber) LIKE :search
                OR LOWER(v.vin) LIKE :search
                OR LOWER(vm.model.name) LIKE :search
                OR LOWER(b.name) LIKE :search
            ')
                ->setParameter('search', '%' . strtolower($searchTerm) . '%');
        }

        // 🔹 Pagination
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }
}
