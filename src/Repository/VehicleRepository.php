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

    /*
     * QueryBuilder principal utilisé pour le dashboard
     * Permet de filtrer les véhicules selon les critères UI
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

        $normalize = function ($value) {
            if ($value === null || $value === '') {
                return [];
            }

            if (is_array($value)) {
                return array_values(array_filter($value, fn($v) => $v !== '' && $v !== null));
            }

            return [$value];
        };

        /*
         * Filtre statut véhicule
         */
        $status = $normalize($filters['status'] ?? null);
        if (!empty($status)) {
            $qb->andWhere('v.status IN (:status)')
                ->setParameter('status', $status);
        }

        /*
         * Filtre type dossier lié (EXISTS)
         */
        $type = $normalize($filters['type'] ?? null);
        if (!empty($type)) {
            $qb->andWhere('EXISTS (
                SELECT 1 FROM App\Entity\Dossier d
                WHERE d.vehicle = v
                AND d.type IN (:type)
            )')
                ->setParameter('type', $type);
        }

        /*
         * Filtre financement dossier lié (EXISTS)
         */
        $financing = $normalize($filters['financing'] ?? null);
        if (!empty($financing)) {
            $qb->andWhere('EXISTS (
                SELECT 1 FROM App\Entity\Dossier d2
                WHERE d2.vehicle = v
                AND d2.financingType IN (:financing)
            )')
                ->setParameter('financing', $financing);
        }

        /*
         * Filtre marque
         */
        $brands = $normalize($filters['brand'] ?? null);
        if (!empty($brands)) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $brands);
        }

        /*
         * Filtre type de carrosserie
         */
        $bodyTypes = $normalize($filters['bodyType'] ?? null);
        if (!empty($bodyTypes)) {
            $qb->andWhere('bt.id IN (:bodyTypes)')
                ->setParameter('bodyTypes', $bodyTypes);
        }

        /*
         * Filtre carburant
         */
        $fuelTypes = $normalize($filters['fuelType'] ?? null);
        if (!empty($fuelTypes)) {
            $qb->andWhere('ft.id IN (:fuelTypes)')
                ->setParameter('fuelTypes', $fuelTypes);
        }

        /*
         * Filtre kilométrage minimum
         */
        if (!empty($filters['mileageMin'])) {
            $qb->andWhere('v.mileage >= :mileageMin')
                ->setParameter('mileageMin', (int) $filters['mileageMin']);
        }

        /*
         * Filtre kilométrage maximum
         */
        if (!empty($filters['mileageMax'])) {
            $qb->andWhere('v.mileage <= :mileageMax')
                ->setParameter('mileageMax', (int) $filters['mileageMax']);
        }

        /*
         * Filtre prix minimum
         */
        if (!empty($filters['priceMin'])) {
            $qb->andWhere('v.price >= :priceMin')
                ->setParameter('priceMin', (float) $filters['priceMin']);
        }

        /*
         * Filtre prix maximum
         */
        if (!empty($filters['priceMax'])) {
            $qb->andWhere('v.price <= :priceMax')
                ->setParameter('priceMax', (float) $filters['priceMax']);
        }

        /*
         * Filtre année d'immatriculation
         */
        $yearMin = $filters['registrationYearMin'] ?? null;
        $yearMax = $filters['registrationYearMax'] ?? null;

        if ($yearMin && ctype_digit((string) $yearMin)) {
            $qb->andWhere('v.firstRegistrationDate >= :yearMin')
                ->setParameter('yearMin', new \DateTime($yearMin . '-01-01'));
        }

        if ($yearMax && ctype_digit((string) $yearMax)) {
            $qb->andWhere('v.firstRegistrationDate <= :yearMax')
                ->setParameter('yearMax', new \DateTime($yearMax . '-12-31'));
        }

        /*
         * Recherche globale
         */
        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere(
                'LOWER(v.registrationNumber) LIKE :search
                OR LOWER(v.vin) LIKE :search
                OR LOWER(m.name) LIKE :search
                OR LOWER(b.name) LIKE :search'
            )->setParameter('search', $search);
        }

        /*
         * Tri par défaut
         */
        return $qb->orderBy('b.name', 'ASC');
    }

    /*
     * Autocomplete léger pour recherche UI
     * Doit rester rapide et indépendant du dashboard
     */
    public function searchForAutocomplete(array $filters = [], ?string $searchTerm = null, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->addSelect('vm', 'b', 'm');

        if (!empty($searchTerm)) {
            $search = '%' . mb_strtolower(trim($searchTerm)) . '%';

            $qb->andWhere(
                'LOWER(m.name) LIKE :search OR LOWER(b.name) LIKE :search'
            )->setParameter('search', $search);
        }

        $qb->setMaxResults($limit);

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

    /*
     * Marques utilisées dans les véhicules existants
     */
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

    /*
     * Types de carrosserie utilisés
     */
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

    /*
     * Types de carburant utilisés
     */
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

    /*
     * Calcul des années disponibles dans les véhicules
     */
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
    /*
 * Statuts disponibles des véhicules
 */
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
}
