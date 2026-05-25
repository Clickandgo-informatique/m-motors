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

    /* QUERY PRINCIPALE */
    public function getFilteredQueryBuilder(array $filters = [], ?string $searchTerm = null, ?User $user = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.bodyType', 'bt')
            ->leftJoin('vm.fuelType', 'ft')
            ->addSelect('vm', 'b', 'm', 'bt', 'ft');


        $normalize = function ($value): array {
            if ($value === null || $value === '') {
                return [];
            }

            if (is_array($value)) {
                $value = array_values(array_filter($value, fn($v) => $v !== '' && $v !== null));
                return $value;
            }

            return [$value];
        };

        /* STATUS */
        $status = $normalize($filters['status'] ?? null);
        if (!empty($status)) {
            $qb->andWhere('v.status IN (:status)')
                ->setParameter('status', $status);
        }

        /* TYPE (vente/location) */
        $type = $normalize($filters['type'] ?? null);
        if (!empty($type)) {
            $qb->andWhere('EXISTS (
                SELECT 1 FROM App\Entity\Dossier d
                WHERE d.vehicle = v
                AND d.type IN (:type)
            )')->setParameter('type', $type);
        }

        /* Financement */
        $financing = $normalize($filters['financing'] ?? null);
        if (!empty($financing)) {
            $qb->andWhere('EXISTS (
                SELECT 1 FROM App\Entity\Dossier d2
                WHERE d2.vehicle = v
                AND d2.financingType IN (:financing)
            )')->setParameter('financing', $financing);
        }

        /* Marques de véhicules */
        $brands = $normalize($filters['brand'] ?? null);
        if (!empty($brands)) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $brands);
        }

        /* Type carrosserie */
        $bodyTypes = $normalize($filters['bodyType'] ?? null);
        if (!empty($bodyTypes)) {
            $qb->andWhere('bt.id IN (:bodyTypes)')
                ->setParameter('bodyTypes', $bodyTypes);
        }

        /* Type carburant / énergie */
        $fuelTypes = $normalize($filters['fuelType'] ?? null);
        if (!empty($fuelTypes)) {
            $qb->andWhere('ft.id IN (:fuelTypes)')
                ->setParameter('fuelTypes', $fuelTypes);
        }

        /* Kilomètrage */
        if (!empty($filters['mileageMin']) && is_numeric($filters['mileageMin'])) {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', (int) $filters['mileageMin']);
        }

        if (!empty($filters['mileageMax']) && is_numeric($filters['mileageMax'])) {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', (int) $filters['mileageMax']);
        }

        /* Prix */
        if (!empty($filters['priceMin']) && is_numeric($filters['priceMin'])) {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', (float) $filters['priceMin']);
        }

        if (!empty($filters['priceMax']) && is_numeric($filters['priceMax'])) {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', (float) $filters['priceMax']);
        }

        /* Années enregistrement véhicule */
        if (!empty($filters['registrationYearMin']) && ctype_digit((string) $filters['registrationYearMin'])) {
            $qb->andWhere('v.firstRegistrationDate >= :yearMin')
                ->setParameter('yearMin', new \DateTime($filters['registrationYearMin'] . '-01-01'));
        }

        if (!empty($filters['registrationYearMax']) && ctype_digit((string) $filters['registrationYearMax'])) {
            $qb->andWhere('v.firstRegistrationDate <= :yearMax')
                ->setParameter('yearMax', new \DateTime($filters['registrationYearMax'] . '-12-31'));
        }

        //   Filtre sur Favoris utilisateur
        if (!empty($filters['favoritesOnly']) && $user instanceof User) {
            $qb
                ->innerJoin('v.favorites', 'fav')
                ->andWhere('fav.user = :currentUser')
                ->setParameter('currentUser', $user);
        }

        /* Recherche */
        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere(
                'LOWER(v.registrationNumber) LIKE :search
                 OR LOWER(v.vin) LIKE :search
                 OR LOWER(m.name) LIKE :search
                 OR LOWER(b.name) LIKE :search'
            )->setParameter('search', $search);
        }

        return $qb->orderBy('b.name', 'ASC');
    }

    /* Autocomplete */
    public function searchForAutocomplete(
        array $filters = [],
        ?string $searchTerm = null,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->addSelect('vm', 'b', 'm');

        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere(
                'LOWER(m.name) LIKE :search
                 OR LOWER(b.name) LIKE :search'
            )->setParameter('search', $search);
        }

        $qb->setMaxResults($limit);

        return array_map(function (Vehicle $v) {
            $vm = $v->getVehicleModel();

            return [
                'id' => $v->getId(),
                'label' => trim(
                    ($vm?->getBrand()?->getName() ?? '') . ' ' .
                        ($vm?->getModel()?->getName() ?? '')
                ),
            ];
        }, $qb->getQuery()->getResult());
    }

    /* FILTER DATASETS */
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

    /* Années enregistrement */
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

    /* Status UI */
    public function getStatuses(): array
    {
        return array_map(
            fn(VehicleStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            VehicleStatus::cases()
        );
    }
    // Prix minimum global
    public function getMinPrice(): int
    {
        $value = $this->createQueryBuilder('v')
            ->select('MIN(v.price)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($value ?? 0);
    }

    // Prix maximum global
    public function getMaxPrice(): int
    {
        $value = $this->createQueryBuilder('v')
            ->select('MAX(v.price)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($value ?? 0);
    }
}
