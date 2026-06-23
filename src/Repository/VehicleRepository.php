<?php

namespace App\Repository;

use App\Entity\BodyType;
use App\Entity\Brand;
use App\Entity\FuelType;
use App\Entity\User;
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

    //Query principale pagination (KNP SAFE)

    public function getFilteredQueryBuilder(
        array $filters = [],
        ?string $searchTerm = null,
        ?User $user = null,
        bool $availableOnly = false


    ): QueryBuilder {
        $qb = $this->createQueryBuilder('v')
            ->select('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.bodyType', 'bt')
            ->leftJoin('vm.fuelType', 'ft')
            ->leftJoin('v.dossiers', 'd')
            ->leftJoin('d.financing', 'f');

        //PAGINATION:évite duplication SQL + COUNT incorrect

        $qb->groupBy('v.id');

        if ($availableOnly) {
            $qb->andWhere('v.status IN (:availableStatuses)')
                ->setParameter('availableStatuses', [
                    VehicleStatus::AVAILABLE_FOR_SALE->value,
                    VehicleStatus::AVAILABLE_FOR_RENT->value,
                ]);
        }

        $normalize = function ($value): array {
            if ($value === null || $value === '') {
                return [];
            }

            if (is_array($value)) {
                return array_values(array_filter($value, fn($v) => $v !== '' && $v !== null));
            }

            return is_scalar($value) ? [(string) $value] : [];
        };

        // STATUS
        if ($status = $normalize($filters['status'] ?? null)) {
            $qb->andWhere('v.status IN (:status)')
                ->setParameter('status', $status);
        }

        // BRAND
        if ($brands = $normalize($filters['brand'] ?? null)) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $brands);
        }

        // BODY TYPE
        if ($bodyTypes = $normalize($filters['bodyType'] ?? null)) {
            $qb->andWhere('bt.id IN (:bodyTypes)')
                ->setParameter('bodyTypes', $bodyTypes);
        }

        // FUEL TYPE
        if ($fuelTypes = $normalize($filters['fuelType'] ?? null)) {
            $qb->andWhere('ft.id IN (:fuelTypes)')
                ->setParameter('fuelTypes', $fuelTypes);
        }

        // FINANCING TYPE
        if ($types = $normalize($filters['financingType'] ?? null)) {
            $qb->andWhere('f.type IN (:financingTypes)')
                ->setParameter('financingTypes', $types);
        }

        // LEASING TYPE
        if ($leasing = $normalize($filters['leasingType'] ?? null)) {
            $qb->andWhere('f.leasingType IN (:leasingTypes)')
                ->setParameter('leasingTypes', $leasing);
        }

        // FINANCING STATUS
        if ($finStatus = $normalize($filters['financingStatus'] ?? null)) {
            $qb->andWhere('f.status IN (:financingStatus)')
                ->setParameter('financingStatus', $finStatus);
        }

        // PRICE MIN
        if (!empty($filters['priceMin'])) {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', (int) $filters['priceMin']);
        }

        // PRICE MAX
        if (!empty($filters['priceMax'])) {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', (int) $filters['priceMax']);
        }

        // MILEAGE MIN
        if (!empty($filters['mileageMin'])) {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', (int) $filters['mileageMin']);
        }

        // MILEAGE MAX
        if (!empty($filters['mileageMax'])) {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', (int) $filters['mileageMax']);
        }

        // YEAR MIN
        if (!empty($filters['registrationYearMin'])) {
            $qb->andWhere('v.firstRegistrationDate >= :dateMin')
                ->setParameter('dateMin', new \DateTime($filters['registrationYearMin'] . '-01-01'));
        }

        // YEAR MAX
        if (!empty($filters['registrationYearMax'])) {
            $qb->andWhere('v.firstRegistrationDate <= :dateMax')
                ->setParameter('dateMax', new \DateTime($filters['registrationYearMax'] . '-12-31'));
        }

        // FAVORITES
        if (!empty($filters['favoritesOnly']) && $user) {
            $qb->join('v.favorites', 'fav')
                ->andWhere('fav.user = :user')
                ->setParameter('user', $user);
        }

        // SEARCH
        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere('(
                LOWER(v.registrationNumber) LIKE :search
                OR LOWER(v.vin) LIKE :search
                OR LOWER(m.name) LIKE :search
                OR LOWER(b.name) LIKE :search
            )')->setParameter('search', $search);
        }

        return $qb->orderBy('v.id', 'DESC');
    }

    //AUTOCOMPLETE

    public function searchForAutocomplete(
        array $filters = [],
        ?string $searchTerm = null,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('v')
            ->select('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->setMaxResults($limit);

        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere(
                'LOWER(m.name) LIKE :search
                OR LOWER(b.name) LIKE :search'
            )->setParameter('search', $search);
        }

        $vehicles = $qb->getQuery()->getResult();

        return array_map(function (Vehicle $v) {
            $vm = $v->getVehicleModel();

            return [
                'id' => $v->getId(),
                'label' => trim(
                    ($vm?->getBrand()?->getName() ?? '') . ' ' .
                        ($vm?->getModel()?->getName() ?? '')
                ),
            ];
        }, $vehicles);
    }

    //FILTER DATASETS

    public function getUsedBrands(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('b')
            ->from(Brand::class, 'b')
            ->where('EXISTS (
                SELECT 1 FROM App\Entity\Vehicle v
                JOIN v.vehicleModel vm
                WHERE vm.brand = b
            )')
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

    public function getRegistrationYears(): array
    {
        $result = $this->createQueryBuilder('v')
            ->select('MIN(v.firstRegistrationDate) as minDate, MAX(v.firstRegistrationDate) as maxDate')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result || !$result['minDate'] || !$result['maxDate']) {
            return ['min' => null, 'max' => null, 'years' => []];
        }

        $min = new \DateTime($result['minDate']);
        $max = new \DateTime($result['maxDate']);

        return [
            'min' => (int) $min->format('Y'),
            'max' => (int) $max->format('Y'),
            'years' => range((int) $min->format('Y'), (int) $max->format('Y')),
        ];
    }

    public function getMinPrice(): int
    {
        return (int) ($this->createQueryBuilder('v')
            ->select('MIN(v.price)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    public function getMaxPrice(): int
    {
        return (int) ($this->createQueryBuilder('v')
            ->select('MAX(v.price)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }
}
