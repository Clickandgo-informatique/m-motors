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
     * QueryBuilder pour récupérer tous les véhicules avec les jointures nécessaires.
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
     * @param array $filters Tableau des filtres
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

        // Normalisation des filtres
        $normalize = function ($value) {
            if ($value === null || $value === '') return [];
            return is_array($value) ? $value : [$value];
        };

        // Filtre marques
        $brands = $normalize($filters['brand'] ?? null);
        if (!empty($brands)) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $brands);
        }

        // Filtre carrosserie
        $bodyTypes = $normalize($filters['bodyType'] ?? null);
        if (!empty($bodyTypes)) {
            $qb->andWhere('bt.id IN (:bodyTypes)')
                ->setParameter('bodyTypes', $bodyTypes);
        }

        // Filtre carburant
        $fuelTypes = $normalize($filters['fuelType'] ?? null);
        if (!empty($fuelTypes)) {
            $qb->andWhere('ft.id IN (:fuelTypes)')
                ->setParameter('fuelTypes', $fuelTypes);
        }

        // Filtre kilométrage
        if (isset($filters['mileageMin']) && $filters['mileageMin'] !== '') {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', (int)$filters['mileageMin']);
        }
        if (isset($filters['mileageMax']) && $filters['mileageMax'] !== '') {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', (int)$filters['mileageMax']);
        }

        // Filtre prix
        if (isset($filters['priceMin']) && $filters['priceMin'] !== '') {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', (float)$filters['priceMin']);
        }
        if (isset($filters['priceMax']) && $filters['priceMax'] !== '') {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', (float)$filters['priceMax']);
        }

        // Filtre années
        if (!empty($filters['yearMin'])) {
            $qb->andWhere('v.firstRegistrationDate >= :yearMin')
                ->setParameter('yearMin', new \DateTime($filters['yearMin'] . '-01-01'));
        }
        if (!empty($filters['yearMax'])) {
            $qb->andWhere('v.firstRegistrationDate <= :yearMax')
                ->setParameter('yearMax', new \DateTime($filters['yearMax'] . '-12-31'));
        }

        if (!empty($searchTerm)) {
            $searchTermParam = '%' . mb_strtolower($searchTerm) . '%';

            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(v.registrationNumber) LIKE :search',
                    'LOWER(v.vin) LIKE :search',
                    'LOWER(m.name) LIKE :search',
                    'LOWER(b.name) LIKE :search'
                )
            )
                ->setParameter('search', $searchTermParam);
        }

        return $qb->orderBy('b.name', 'ASC');
    }

    /**
     * Pagination KnpPaginator
     */
    public function searchForPaginator(array $filters = [], ?string $searchTerm = null): QueryBuilder
    {
        return $this->getFilteredQueryBuilder($filters, $searchTerm);
    }

    /**
     * Résultats limités pour autocomplete ou API
     */
    public function searchForAutocomplete(
        array $filters = [],
        ?string $searchTerm = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->getFilteredQueryBuilder($filters, $searchTerm);

        if ($limit !== null) $qb->setMaxResults($limit);
        if ($offset !== null) $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Marques utilisées
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
            if ($brand) $brands[] = $brand;
        }

        return $brands;
    }

    /**
     * Carrosseries utilisées
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
            if ($bt) $bodyTypes[] = $bt;
        }

        return $bodyTypes;
    }

    /**
     * Carburants utilisés
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
            if ($ft) $fuelTypes[] = $ft;
        }

        return $fuelTypes;
    }

    /**
     * Bornes années pour slider
     */
    public function getRegistrationYears(): array
    {
        $qb = $this->createQueryBuilder('v')
            ->select('MIN(v.firstRegistrationDate) AS minDate')
            ->addSelect('MAX(v.firstRegistrationDate) AS maxDate')
            ->where('v.firstRegistrationDate IS NOT NULL');

        $result = $qb->getQuery()->getOneOrNullResult();

        if (!$result || !$result['minDate'] || !$result['maxDate']) {
            return ['min' => null, 'max' => null, 'years' => []];
        }

        $minYear = (int)(new \DateTime($result['minDate']))->format('Y');
        $maxYear = (int)(new \DateTime($result['maxDate']))->format('Y');

        return [
            'min' => $minYear,
            'max' => $maxYear,
            'years' => range($minYear, $maxYear),
        ];
    }
}
