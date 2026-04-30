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

class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    public function getFilteredQueryBuilder(array $filters = [], ?string $searchTerm = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.bodyType', 'bt')
            ->leftJoin('vm.fuelType', 'ft')
            ->leftJoin('v.dossiers', 'd')
            ->addSelect('vm', 'b', 'm', 'bt', 'ft', 'd');

        $normalize = fn($v) => empty($v) ? [] : (is_array($v) ? $v : [$v]);

        // STATUS
        $status = $normalize($filters['status'] ?? null);
        if ($status) {
            $qb->andWhere('v.status IN (:status)')
                ->setParameter('status', $status);
        }

        // TYPE (dossiers)
        $type = $normalize($filters['type'] ?? null);
        if ($type) {
            $qb->andWhere('d.type IN (:type)')
                ->setParameter('type', $type);
        }

        // FINANCING
        $financing = $normalize($filters['financing'] ?? null);
        if ($financing) {
            $qb->andWhere('d.financingType IN (:financing)')
                ->setParameter('financing', $financing);
        }

        // BRAND
        $brands = $normalize($filters['brand'] ?? null);
        if ($brands) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $brands);
        }

        // BODY TYPE
        $bodyTypes = $normalize($filters['bodyType'] ?? null);
        if ($bodyTypes) {
            $qb->andWhere('bt.id IN (:bodyTypes)')
                ->setParameter('bodyTypes', $bodyTypes);
        }

        // FUEL TYPE
        $fuelTypes = $normalize($filters['fuelType'] ?? null);
        if ($fuelTypes) {
            $qb->andWhere('ft.id IN (:fuelTypes)')
                ->setParameter('fuelTypes', $fuelTypes);
        }

        // MILEAGE
        if (isset($filters['mileageMin']) && $filters['mileageMin'] !== '') {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', (int)$filters['mileageMin']);
        }

        if (isset($filters['mileageMax']) && $filters['mileageMax'] !== '') {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', (int)$filters['mileageMax']);
        }

        // PRICE
        if (isset($filters['priceMin']) && $filters['priceMin'] !== '') {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', (float)$filters['priceMin']);
        }

        if (isset($filters['priceMax']) && $filters['priceMax'] !== '') {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', (float)$filters['priceMax']);
        }

        // YEAR
        $yearMin = $filters['registrationYearMin'] ?? null;
        $yearMax = $filters['registrationYearMax'] ?? null;

        if ($yearMin !== null && $yearMin !== '') {
            $qb->andWhere('v.firstRegistrationDate >= :yearMin')
                ->setParameter('yearMin', new \DateTime($yearMin . '-01-01'));
        }

        if ($yearMax !== null && $yearMax !== '') {
            $qb->andWhere('v.firstRegistrationDate <= :yearMax')
                ->setParameter('yearMax', new \DateTime($yearMax . '-12-31'));
        }

        // SEARCH
        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(v.registrationNumber) LIKE :search',
                    'LOWER(v.vin) LIKE :search',
                    'LOWER(m.name) LIKE :search',
                    'LOWER(b.name) LIKE :search'
                )
            )->setParameter('search', $search);
        }

        return $qb->orderBy('b.name', 'ASC');
    }

    public function searchForAutocomplete(array $filters = [], ?string $searchTerm = null, int $limit = 10): array
    {
        $qb = $this->getFilteredQueryBuilder($filters, $searchTerm)
            ->setMaxResults($limit);

        $vehicles = $qb->getQuery()->getResult();

        return array_map(function (Vehicle $v) {
            $vm = $v->getVehicleModel();

            $brand = $vm?->getBrand()?->getName() ?? '';
            $model = $vm?->getModel()?->getName() ?? '';
            $registration = $v->getRegistrationNumber() ?? '';

            return [
                'id' => $v->getId(),

                // label affiché dans le dropdown
                'label' => trim($brand . ' ' . $model . ' ' . $registration),

                // valeur réellement utilisée pour la recherche
                'value' => $registration ?: ($brand . ' ' . $model)
            ];
        }, $vehicles);
    }

    // =========================
    // FILTER OPTIONS (FIXED)
    // =========================

    public function getUsedBrands(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('b')
            ->from(Brand::class, 'b')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getUsedBodyTypes(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('bt')
            ->from(BodyType::class, 'bt')
            ->orderBy('bt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getUsedFuelTypes(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ft')
            ->from(FuelType::class, 'ft')
            ->orderBy('ft.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // =========================
    // YEARS
    // =========================

    public function getRegistrationYears(): array
    {
        $result = $this->createQueryBuilder('v')
            ->select('MIN(v.firstRegistrationDate) as minDate, MAX(v.firstRegistrationDate) as maxDate')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result || empty($result['minDate']) || empty($result['maxDate'])) {
            return [
                'min' => null,
                'max' => null,
                'years' => []
            ];
        }

        // 🔧 Sécurisation des types Doctrine (string ou DateTime)
        $minDate = $result['minDate'] instanceof \DateTimeInterface
            ? $result['minDate']
            : new \DateTime($result['minDate']);

        $maxDate = $result['maxDate'] instanceof \DateTimeInterface
            ? $result['maxDate']
            : new \DateTime($result['maxDate']);

        $minYear = (int) $minDate->format('Y');
        $maxYear = (int) $maxDate->format('Y');

        return [
            'min' => $minYear,
            'max' => $maxYear,
            'years' => range($minYear, $maxYear),
        ];
    }
    public function getStockCounts(array $filters = [], ?string $searchTerm = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.bodyType', 'bt')
            ->leftJoin('vm.fuelType', 'ft')
            ->leftJoin('v.dossiers', 'd')
            ->addSelect('vm', 'b', 'm', 'bt', 'ft', 'd');

        // réutilisation filtres
        $this->applyFiltersToQueryBuilder($qb, $filters);

        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(v.registrationNumber) LIKE :search',
                    'LOWER(v.vin) LIKE :search',
                    'LOWER(m.name) LIKE :search',
                    'LOWER(b.name) LIKE :search'
                )
            )->setParameter('search', $search);
        }

        $qb->select('v.status, COUNT(v.id) AS count')
            ->groupBy('v.status');

        $rows = $qb->getQuery()->getResult();

        // init depuis enum (IMPORTANT)
        $counts = ['total' => 0];

        foreach (VehicleStatus::cases() as $case) {
            $counts[$case->value] = 0;
        }

        foreach ($rows as $row) {
            $status = $row['status'];
            $count = (int) $row['count'];

            $counts[$status] = $count;
            $counts['total'] += $count;
        }

        return $counts;
    }
    public function getStockCountsGrouped(array $filters = []): array
    {
        $counts = $this->getStockCounts($filters);

        return [
            'stock' => $counts['available'] + $counts['reserved'],
            'in_use' => $counts['rented'],
            'out' => $counts['sold'],
            'maintenance' => $counts['maintenance'],
            'ordered' => $counts['ordered'],
            'total' => $counts['total'],
        ];
    }
}
