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

    // =========================================================
    // QUERY BUILDER PRINCIPAL
    // =========================================================

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

        $normalize = function ($value) {
            if ($value === null || $value === '') {
                return [];
            }

            if (is_array($value)) {
                return array_filter($value, fn($v) => $v !== '' && $v !== null);
            }

            return [$value];
        };

        // =====================================================
        // STATUS
        // =====================================================

        $status = $normalize($filters['status'] ?? null);
        if (!empty($status)) {
            $qb->andWhere('v.status IN (:status)')
                ->setParameter('status', $status);
        }

        // =====================================================
        // DOSSIERS TYPE
        // =====================================================

        $type = $normalize($filters['type'] ?? null);
        if (!empty($type)) {
            $qb->andWhere('d.type IN (:type)')
                ->setParameter('type', $type);
        }

        // =====================================================
        // FINANCING
        // =====================================================

        $financing = $normalize($filters['financing'] ?? null);
        if (!empty($financing)) {
            $qb->andWhere('d.financingType IN (:financing)')
                ->setParameter('financing', $financing);
        }

        // =====================================================
        // BRAND
        // =====================================================

        $brands = $normalize($filters['brand'] ?? null);
        if (!empty($brands)) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $brands);
        }

        // =====================================================
        // BODY TYPE
        // =====================================================

        $bodyTypes = $normalize($filters['bodyType'] ?? null);
        if (!empty($bodyTypes)) {
            $qb->andWhere('bt.id IN (:bodyTypes)')
                ->setParameter('bodyTypes', $bodyTypes);
        }

        // =====================================================
        // FUEL TYPE
        // =====================================================

        $fuelTypes = $normalize($filters['fuelType'] ?? null);
        if (!empty($fuelTypes)) {
            $qb->andWhere('ft.id IN (:fuelTypes)')
                ->setParameter('fuelTypes', $fuelTypes);
        }

        // =====================================================
        // MILEAGE
        // =====================================================

        if (!empty($filters['mileageMin'])) {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', (int) $filters['mileageMin']);
        }

        if (!empty($filters['mileageMax'])) {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', (int) $filters['mileageMax']);
        }

        // =====================================================
        // PRICE
        // =====================================================

        if (!empty($filters['priceMin'])) {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', (float) $filters['priceMin']);
        }

        if (!empty($filters['priceMax'])) {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', (float) $filters['priceMax']);
        }

        // =====================================================
        // YEAR
        // =====================================================

        $yearMin = $filters['registrationYearMin'] ?? null;
        $yearMax = $filters['registrationYearMax'] ?? null;

        if ($yearMin) {
            $qb->andWhere('v.firstRegistrationDate >= :yearMin')
                ->setParameter('yearMin', new \DateTime($yearMin . '-01-01'));
        }

        if ($yearMax) {
            $qb->andWhere('v.firstRegistrationDate <= :yearMax')
                ->setParameter('yearMax', new \DateTime($yearMax . '-12-31'));
        }

        // =====================================================
        // SEARCH GLOBAL
        // =====================================================

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

        // =====================================================
        // ORDER SAFE (DOCTRINE COMPATIBLE)
        // =====================================================

        return $qb->orderBy('b.name', 'ASC');
    }

    // =========================================================
    // AUTOCOMPLETE
    // =========================================================

    public function searchForAutocomplete(array $filters = [], ?string $searchTerm = null, int $limit = 10): array
    {
        $qb = $this->getFilteredQueryBuilder($filters, $searchTerm)
            ->setMaxResults($limit);

        $vehicles = $qb->getQuery()->getResult();

        return array_map(function (Vehicle $v) {
            $vm = $v->getVehicleModel();

            return [
                'id' => $v->getId(),
                'label' => trim(
                    ($vm?->getBrand()?->getName() ?? '') . ' ' .
                        ($vm?->getModel()?->getName() ?? '')
                )
            ];
        }, $vehicles);
    }

    // =========================================================
    // FILTER OPTIONS
    // =========================================================

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

    // =========================================================
    // YEARS
    // =========================================================

    public function getRegistrationYears(): array
    {
        $result = $this->createQueryBuilder('v')
            ->select('MIN(v.firstRegistrationDate) as minDate, MAX(v.firstRegistrationDate) as maxDate')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result || !$result['minDate'] || !$result['maxDate']) {
            return [
                'min' => null,
                'max' => null,
                'years' => []
            ];
        }

        $minDate = $result['minDate'] instanceof \DateTimeInterface
            ? $result['minDate']
            : new \DateTime($result['minDate']);

        $maxDate = $result['maxDate'] instanceof \DateTimeInterface
            ? $result['maxDate']
            : new \DateTime($result['maxDate']);

        return [
            'min' => (int) $minDate->format('Y'),
            'max' => (int) $maxDate->format('Y'),
            'years' => range(
                (int) $minDate->format('Y'),
                (int) $maxDate->format('Y')
            ),
        ];
    }
}
