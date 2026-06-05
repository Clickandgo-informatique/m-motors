<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Enum\DossierType;
use App\Service\VehicleWorkflowService;

class DossierFinanceService
{
    public function __construct(
        private VehicleWorkflowService $vehicleWorkflow
    ) {}

    public function approve(Dossier $dossier): void
    {
        $vehicle = $dossier->getVehicle();

        if (!$vehicle) {
            return;
        }

        if ($dossier->getType() === DossierType::PURCHASE) {
            $this->vehicleWorkflow->sell($vehicle);
            return;
        }

        if ($dossier->getType() === DossierType::RENTAL) {
            $this->vehicleWorkflow->rent($vehicle);
            return;
        }
    }
}
