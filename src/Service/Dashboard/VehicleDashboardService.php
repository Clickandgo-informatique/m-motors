<?php

namespace App\Service\Dashboard;

use App\Repository\VehicleRepository;

class VehicleDashboardService
{
    public function __construct(
        private VehicleRepository $vehicleRepository
    ) {}

    // =========================================================
    // KPI GLOBAL
    // =========================================================

    public function getStats(): array
    {
        return $this->vehicleRepository->createQueryBuilder('v')
            ->select('COUNT(v.id) as total')
            ->addSelect("SUM(CASE WHEN v.status IN ('available_sale','available_rent') THEN 1 ELSE 0 END) as available")
            ->addSelect("SUM(CASE WHEN v.status = 'rented' THEN 1 ELSE 0 END) as rented")
            ->addSelect("SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance")
            ->getQuery()
            ->getSingleResult();
    }

    // =========================================================
    // STOCK PAR MODELE
    // =========================================================

    public function getStockByModel(): array
    {
        return $this->vehicleRepository->createQueryBuilder('v')
            ->select('IDENTITY(v.vehicleModel) as modelId')
            ->addSelect('COUNT(v.id) as total')
            ->addSelect("SUM(CASE WHEN v.status = 'available_sale' THEN 1 ELSE 0 END) as sale")
            ->addSelect("SUM(CASE WHEN v.status = 'available_rent' THEN 1 ELSE 0 END) as rent")
            ->addSelect("SUM(CASE WHEN v.status = 'rented' THEN 1 ELSE 0 END) as rented")
            ->addSelect("SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance")
            ->groupBy('v.vehicleModel')
            ->getQuery()
            ->getArrayResult();
    }

    // =========================================================
    // USAGE COMMERCIAL
    // =========================================================

    public function getUsageDistribution(): array
    {
        return $this->vehicleRepository->createQueryBuilder('v')
            ->select('vm.id as modelId')
            ->addSelect('COUNT(v.id) as total')
            ->addSelect("SUM(CASE WHEN v.usageType = 'sale' THEN 1 ELSE 0 END) as sale")
            ->addSelect("SUM(CASE WHEN v.usageType = 'rent' THEN 1 ELSE 0 END) as rent")
            ->addSelect("SUM(CASE WHEN v.usageType = 'both' THEN 1 ELSE 0 END) as both")
            ->join('v.vehicleModel', 'vm')
            ->groupBy('vm.id')
            ->getQuery()
            ->getArrayResult();
    }
}
