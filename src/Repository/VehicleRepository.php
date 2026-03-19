<?php

namespace App\Repository;

use App\Entity\Vehicle;
use App\Entity\Brand;
use App\Entity\Model;
use App\Entity\Variant;
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
     * Base QueryBuilder SAFE pour KNP (sans addSelect)
     */
    private function baseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.brand', 'b');
    }

    /**
     * Liste complète (compatible paginator)
     */
    public function findAllQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->addSelect('vm', 'b')
            ->orderBy('v.id', 'DESC');
    }

    /**
     * Recherche pour KnpPaginator
     */
    public function searchQueryBuilder(string $term): QueryBuilder
    {
        $qb = $this->baseQueryBuilder();

        if (trim($term) !== '') {

            $terms = array_filter(explode(' ', strtolower($term)));

            foreach ($terms as $index => $word) {

                $param = "term_$index";

                $qb->andWhere("
                    (
                        LOWER(b.name) LIKE :$param
                        OR LOWER(m.name) LIKE :$param
                        OR LOWER(v.status) LIKE :$param
                        OR LOWER(v.registrationNumber) LIKE :$param
                        OR LOWER(v.vin) LIKE :$param
                    )
                ")
                    ->setParameter($param, "%$word%");
            }
        }

        return $qb->orderBy('b.name', 'ASC');
    }

    /**
     * Compteur custom
     */
    public function countSearch(string $term): int
    {
        if (trim($term) === '') {
            return 0;
        }

        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v.id)')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.brand', 'b');

        $terms = array_filter(explode(' ', strtolower($term)));

        foreach ($terms as $index => $word) {

            $param = "term_$index";

            $qb->andWhere("
                (
                    LOWER(b.name) LIKE :$param
                    OR LOWER(m.name) LIKE :$param
                    OR LOWER(v.status) LIKE :$param
                    OR LOWER(v.registrationNumber) LIKE :$param
                    OR LOWER(v.vin) LIKE :$param
                )
            ")
                ->setParameter($param, "%$word%");
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Pagination custom
     */
    public function searchPaginated(string $term, int $limit, int $offset): array
    {
        if (trim($term) === '') {
            return [];
        }

        $qb = $this->baseQueryBuilder();

        $terms = array_filter(explode(' ', strtolower($term)));

        foreach ($terms as $index => $word) {

            $param = "term_$index";

            $qb->andWhere("
                (
                    LOWER(b.name) LIKE :$param
                    OR LOWER(m.name) LIKE :$param
                    OR LOWER(v.status) LIKE :$param
                    OR LOWER(v.registrationNumber) LIKE :$param
                    OR LOWER(v.vin) LIKE :$param
                )
            ")
                ->setParameter($param, "%$word%");
        }

        $qb->orderBy('b.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche par signature métier
     */
    public function findOneBySignature(
        Brand $brand,
        Model $model,
        ?Variant $variant = null
    ): ?Vehicle {

        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.brand', 'b')
            ->andWhere('b = :brand')
            ->andWhere('m = :model')
            ->setParameter('brand', $brand)
            ->setParameter('model', $model);

        if ($variant !== null) {
            $qb->leftJoin('vm.variant', 'va')
                ->andWhere('va = :variant')
                ->setParameter('variant', $variant);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
    //Filtrage dynamique des véhicules coté utilisateur client
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.vehicleModel', 'm')
            ->join('m.brand', 'b');

        // BRAND
        if (!empty($filters['brand'])) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $filters['brand']);
        }

        // FUEL
        if (!empty($filters['fuel'])) {
            $qb->andWhere('m.fuelType IN (:fuel)')
                ->setParameter('fuel', $filters['fuel']);
        }

        // PRIX MIN
        if (!empty($filters['priceMin'])) {
            $qb->andWhere('v.price >= :min')
                ->setParameter('min', $filters['priceMin']);
        }

        // PRIX MAX
        if (!empty($filters['priceMax'])) {
            $qb->andWhere('v.price <= :max')
                ->setParameter('max', $filters['priceMax']);
        }

        //Kilométrage
        if (isset($filters['mileageMin'], $filters['mileageMax'])) {
            $qb->andWhere('v.mileage >= :min AND v.mileage <= :max')
                ->setParameter('min', $filters['mileageMin'])
                ->setParameter('max', $filters['mileageMax']);
        }


        return $qb->getQuery()->getResult();
    }
}
