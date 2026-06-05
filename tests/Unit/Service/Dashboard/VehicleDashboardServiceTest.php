<?php

namespace App\Tests\Unit\Service\Dashboard;

use App\Repository\VehicleRepository;
use App\Service\Dashboard\VehicleDashboardService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class VehicleDashboardServiceTest extends TestCase
{
    public function testGetStats(): void
    {
        // vérifie le retour des statistiques globales du parc

        $expectedResult = [
            'total' => 100,
            'available' => 60,
            'rented' => 25,
            'maintenance' => 15,
        ];

        $query = $this->createMock(Query::class);

        $query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn($expectedResult);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('COUNT(v.id) as total')
            ->willReturnSelf();

        $queryBuilder->expects($this->exactly(3))
            ->method('addSelect')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(VehicleRepository::class);

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('v')
            ->willReturn($queryBuilder);

        $service = new VehicleDashboardService($repository);

        $this->assertSame($expectedResult, $service->getStats());
    }

    public function testGetStockByModel(): void
    {
        // vérifie le retour du stock regroupé par modèle

        $expectedResult = [
            [
                'modelId' => 1,
                'total' => 10,
                'sale' => 4,
                'rent' => 3,
                'rented' => 2,
                'maintenance' => 1,
            ],
        ];

        $query = $this->createMock(Query::class);

        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($expectedResult);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('IDENTITY(v.vehicleModel) as modelId')
            ->willReturnSelf();

        $queryBuilder->expects($this->exactly(5))
            ->method('addSelect')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('v.vehicleModel')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(VehicleRepository::class);

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('v')
            ->willReturn($queryBuilder);

        $service = new VehicleDashboardService($repository);

        $this->assertSame($expectedResult, $service->getStockByModel());
    }

    public function testGetUsageDistribution(): void
    {
        // vérifie le retour de la répartition commerciale par modèle

        $expectedResult = [
            [
                'modelId' => 1,
                'total' => 20,
                'sale' => 8,
                'rent' => 7,
                'both' => 5,
            ],
        ];

        $query = $this->createMock(Query::class);

        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($expectedResult);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('vm.id as modelId')
            ->willReturnSelf();

        $queryBuilder->expects($this->exactly(4))
            ->method('addSelect')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('join')
            ->with('v.vehicleModel', 'vm')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('vm.id')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(VehicleRepository::class);

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('v')
            ->willReturn($queryBuilder);

        $service = new VehicleDashboardService($repository);

        $this->assertSame($expectedResult, $service->getUsageDistribution());
    }
}