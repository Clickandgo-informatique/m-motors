<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Enum\DossierType;

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

        if ($dossier->getType() === DossierType::SALE) {
            $this->vehicleWorkflow->sell($vehicle);
            return;
        }

        if ($dossier->getType() === DossierType::RENTAL) {
            $this->vehicleWorkflow->rent($vehicle);
            return;
        }
    }
}
