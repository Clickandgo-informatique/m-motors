<?php

namespace App\EventSubscriber;

use App\Entity\Dossier;
use App\Enum\DossierType;
use App\Service\Financing\DossierFinancingService;
use App\Service\VehicleWorkflowService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class DossierWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VehicleWorkflowService $vehicleWorkflow,
        private DossierFinancingService $financingService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.dossier.transition' => 'onTransition',
        ];
    }

    public function onTransition(Event $event): void
    {
        $dossier = $event->getSubject();

        if (!$dossier instanceof Dossier) {
            return;
        }

        $vehicle = $dossier->getVehicle();

        if (!$vehicle) {
            return;
        }

        $transition = $event->getTransition()->getName();

        // =========================================================
        // SELECT VEHICLE
        // =========================================================
        if ($transition === 'select_vehicle') {
            $this->vehicleWorkflow->reserve($vehicle);
            return;
        }

        // =========================================================
        // FINANCING APPROVAL (delegué au service métier)
        // =========================================================
        if ($transition === 'approve_financing') {
            $this->financingService->approve($dossier);
            return;
        }

        // =========================================================
        // CANCEL DOSSIER
        // =========================================================
        if ($transition === 'cancel') {
            $this->vehicleWorkflow->return($vehicle);
            return;
        }
    }
}
