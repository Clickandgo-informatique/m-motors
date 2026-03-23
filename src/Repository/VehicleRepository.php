<?php

namespace App\Repository;

use App\Entity\Vehicle;
use App\Entity\Brand;
use App\Entity\BodyType;
use App\Entity\FuelType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Vehicle.
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * QueryBuilder pour récupérer tous les véhicules
     * avec les jointures nécessaires pour l'affichage.
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
     * Construit un QueryBuilder avec filtres dynamiques.
     *
     * @param array $filters Tableau des filtres (brand, bodyType, fuelType, price, mileage, etc.)
     * @param string|null $searchTerm Terme de recherche texte
     */
    public function getFilteredQueryBuilder(array $filters = [], ?string $searchTerm = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.bodyType', 'bt')
            ->leftJoin('vm.fuelType', 'ft')
            ->addSelect('vm', 'b', 'm', 'bt', 'ft');

        /**
         * Filtre par marques
         */
        if (!empty($filters['brand'])) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $filters['brand']);
        }

        /**
         * Filtre par type de carrosserie
         */
        if (!empty($filters['bodyType'])) {
            $qb->andWhere('bt.id IN (:bodyTypes)')
                ->setParameter('bodyTypes', $filters['bodyType']);
        }

        /**
         * Filtre par carburant
         */
        if (!empty($filters['fuelType'])) {
            $qb->andWhere('ft.id IN (:fuelTypes)')
                ->setParameter('fuelTypes', $filters['fuelType']);
        }

        /**
         * Filtre kilométrage minimum
         */
        if (isset($filters['mileageMin']) && $filters['mileageMin'] !== '') {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', $filters['mileageMin']);
        }

        /**
         * Filtre kilométrage maximum
         */
        if (isset($filters['mileageMax']) && $filters['mileageMax'] !== '') {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', $filters['mileageMax']);
        }

        /**
         * Filtre prix minimum
         */
        if (isset($filters['priceMin']) && $filters['priceMin'] !== '') {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', $filters['priceMin']);
        }

        /**
         * Filtre prix maximum
         */
        if (isset($filters['priceMax']) && $filters['priceMax'] !== '') {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', $filters['priceMax']);
        }

        /**
         * Recherche texte multi-champs
         */
        if ($searchTerm) {
            $qb->andWhere('
                LOWER(v.registrationNumber) LIKE :search
                OR LOWER(v.vin) LIKE :search
                OR LOWER(m.name) LIKE :search
                OR LOWER(b.name) LIKE :search
            ')
                ->setParameter('search', '%' . strtolower($searchTerm) . '%');
        }

        return $qb->orderBy('b.name', 'ASC');
    }

    /**
     * Utilisé pour la pagination avec KnpPaginator
     */
    public function searchForPaginator(array $filters = [], ?string $searchTerm = null): QueryBuilder
    {
        return $this->getFilteredQueryBuilder($filters, $searchTerm);
    }

    /**
     * Utilisé pour des résultats limités (autocomplete, API, etc.)
     */
    public function searchForAutocomplete(
        array $filters = [],
        ?string $searchTerm = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->getFilteredQueryBuilder($filters, $searchTerm);

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les marques réellement utilisées dans les véhicules
     */
    public function getUsedBrands(): array
    {
        $em = $this->getEntityManager();

        $results = $em->createQueryBuilder()
            ->select('b.id, b.name')
            ->from(Vehicle::class, 'v')
            ->innerJoin('v.vehicleModel', 'vm')
            ->innerJoin('vm.brand', 'b')
            ->groupBy('b.id, b.name')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $brands = [];
        foreach ($results as $row) {
            $brand = $em->getRepository(Brand::class)->find($row['id']);
            if ($brand) {
                $brands[] = $brand;
            }
        }

        return $brands;
    }

    /**
     * Retourne les types de carrosserie utilisés
     */
    public function getUsedBodyTypes(): array
    {
        $em = $this->getEntityManager();

        $results = $em->createQueryBuilder()
            ->select('bt.id, bt.name')
            ->from(Vehicle::class, 'v')
            ->innerJoin('v.vehicleModel', 'vm')
            ->innerJoin('vm.bodyType', 'bt')
            ->groupBy('bt.id, bt.name')
            ->orderBy('bt.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $bodyTypes = [];
        foreach ($results as $row) {
            $bt = $em->getRepository(BodyType::class)->find($row['id']);
            if ($bt) {
                $bodyTypes[] = $bt;
            }
        }

        return $bodyTypes;
    }

    /**
     * Retourne les carburants utilisés
     */
    public function getUsedFuelTypes(): array
    {
        $em = $this->getEntityManager();

        $results = $em->createQueryBuilder()
            ->select('ft.id, ft.name')
            ->from(Vehicle::class, 'v')
            ->innerJoin('v.vehicleModel', 'vm')
            ->innerJoin('vm.fuelType', 'ft')
            ->groupBy('ft.id, ft.name')
            ->orderBy('ft.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $fuelTypes = [];
        foreach ($results as $row) {
            $ft = $em->getRepository(FuelType::class)->find($row['id']);
            if ($ft) {
                $fuelTypes[] = $ft;
            }
        }

        return $fuelTypes;
    }
}
