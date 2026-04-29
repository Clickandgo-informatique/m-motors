<?php

namespace App\Repository;

use App\Entity\BodyType;
use App\Entity\Brand;
use App\Entity\FuelType;
use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
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
            ->orderBy('b.name', 'ASC');
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
            ->addSelect('vm', 'b', 'm', 'bt', 'ft')
            // 🔥 Filtrage métier : uniquement véhicules visibles
            ->andWhere('v.status IN (:visibleStatuses)')
            ->setParameter('visibleStatuses', [
                VehicleStatus::AVAILABLE_FOR_SALE,
                VehicleStatus::AVAILABLE_FOR_RENT,
                VehicleStatus::RESERVED
            ]);

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

        // Recherche texte
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
        ?int $limit = 10,
        ?int $offset = null
    ): array {
        $qb = $this->getFilteredQueryBuilder($filters, $searchTerm);

        // Amélioration UX autocomplete
        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->addOrderBy(
                "CASE 
                    WHEN LOWER(CONCAT(b.name, ' ', m.name)) LIKE :search THEN 0
                    WHEN LOWER(b.name) LIKE :search THEN 1
                    WHEN LOWER(m.name) LIKE :search THEN 2
                    ELSE 3
                END",
                'ASC'
            )->setParameter('search', $search);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        $vehicles = $qb->getQuery()->getResult();

        return array_map(function (Vehicle $v) {
            $vm = $v->getVehicleModel();

            return [
                'id' => $v->getId(),
                'label' => trim(
                    ($vm?->getBrand()?->getName() ?? '') . ' ' .
                        ($vm?->getModel()?->getName() ?? '') . ' ' .
                        ($vm?->getBodyType()?->getName() ?? '')
                )
            ];
        }, $vehicles);
    }

    /**
     * Marques utilisées (optimisé, sans N+1)
     */
    public function getUsedBrands(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT b')
            ->from(Vehicle::class, 'v')
            ->innerJoin('v.vehicleModel', 'vm')
            ->innerJoin('vm.brand', 'b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Carrosseries utilisées (optimisé)
     */
    public function getUsedBodyTypes(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT bt')
            ->from(Vehicle::class, 'v')
            ->innerJoin('v.vehicleModel', 'vm')
            ->innerJoin('vm.bodyType', 'bt')
            ->orderBy('bt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Carburants utilisés (optimisé)
     */
    public function getUsedFuelTypes(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT ft')
            ->from(Vehicle::class, 'v')
            ->innerJoin('v.vehicleModel', 'vm')
            ->innerJoin('vm.fuelType', 'ft')
            ->orderBy('ft.name', 'ASC')
            ->getQuery()
            ->getResult();
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
