<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Service\Dossier\DossierFinanceService;
use App\Service\VehicleWorkflowService;
use PHPUnit\Framework\TestCase;

class DossierFinanceServiceTest extends TestCase
{
    public function testApproveDoesNothingWhenVehicleIsMissing(): void
    {
        // vérifie qu'aucune action n'est exécutée sans véhicule

        $dossier = new Dossier();
        $dossier->setType(DossierType::PURCHASE);

        $vehicleWorkflow = $this->createMock(
            VehicleWorkflowService::class
        );

        $vehicleWorkflow->expects($this->never())
            ->method('sell');

        $vehicleWorkflow->expects($this->never())
            ->method('rent');

        $service = new DossierFinanceService(
            $vehicleWorkflow
        );

        $service->approve($dossier);
    }

    public function testApproveSellsVehicleForPurchaseDossier(): void
    {
        // vérifie la vente du véhicule pour un dossier achat

        $vehicle = $this->createMock(Vehicle::class);

        $dossier = new Dossier();
        $dossier->setVehicle($vehicle);
        $dossier->setType(DossierType::PURCHASE);

        $vehicleWorkflow = $this->createMock(
            VehicleWorkflowService::class
        );

        $vehicleWorkflow->expects($this->once())
            ->method('sell')
            ->with($vehicle);

        $vehicleWorkflow->expects($this->never())
            ->method('rent');

        $service = new DossierFinanceService(
            $vehicleWorkflow
        );

        $service->approve($dossier);
    }

    public function testApproveRentsVehicleForRentalDossier(): void
    {
        // vérifie la mise en location du véhicule pour un dossier location

        $vehicle = $this->createMock(Vehicle::class);

        $dossier = new Dossier();
        $dossier->setVehicle($vehicle);
        $dossier->setType(DossierType::RENTAL);

        $vehicleWorkflow = $this->createMock(
            VehicleWorkflowService::class
        );

        $vehicleWorkflow->expects($this->once())
            ->method('rent')
            ->with($vehicle);

        $vehicleWorkflow->expects($this->never())
            ->method('sell');

        $service = new DossierFinanceService(
            $vehicleWorkflow
        );

        $service->approve($dossier);
    }
}