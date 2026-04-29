<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;

class VehicleFilterService
{
    public function apply(QueryBuilder $qb, array $filters, ?string $searchTerm = null): QueryBuilder
    {
        dump($filters);
        $normalize = fn($v) => empty($v) ? [] : (is_array($v) ? $v : [$v]);

        // JOIN DOSSIERS (OK ici car spécifique au filtre)
        $qb->leftJoin('v.dossiers', 'd')->addSelect('d');

        // STATUS
        $status = $normalize($filters['status'] ?? null);
        if ($status) {
            $qb->andWhere('v.status IN (:status)')
                ->setParameter('status', $status);
        }

        // TYPE
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

        // SEARCH
        if (!empty($searchTerm)) {

            $searchTerm = trim($searchTerm);
            $search = '%' . mb_strtolower($searchTerm) . '%';

            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(v.registrationNumber) LIKE :search',
                    'LOWER(v.vin) LIKE :search',
                    'LOWER(m.name) LIKE :search',
                    'LOWER(b.name) LIKE :search',
                    "LOWER(CONCAT(b.name, ' ', m.name)) LIKE :search"
                )
            )
                ->setParameter('search', $search);
        }

        return $qb;
    }

    public function count(array $filters): int
    {
        $count = 0;

        foreach ($filters as $v) {
            if (is_array($v)) {
                $count += count(array_filter($v));
            } elseif (!empty($v)) {
                $count++;
            }
        }

        return $count;
    }
}
