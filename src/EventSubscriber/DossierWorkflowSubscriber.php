<?php

namespace App\EventSubscriber;

use App\Entity\Dossier;
use App\Service\Dossier\DossierWorkflowAuditService;
use App\Service\Financing\DossierFinancingService;
use App\Service\VehicleWorkflowService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowEvents;

/**
 * subscriber du workflow dossier
 *
 * rôle :
 * - exécuter les effets de bord métier uniquement
 * - la persistance des logs est centralisée dans l'audit service
 */
class DossierWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VehicleWorkflowService $vehicleWorkflow,
        private DossierFinancingService $financingService,
        private DossierWorkflowAuditService $audit
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            WorkflowEvents::TRANSITION => 'onTransition',
        ];
    }

    public function onTransition(TransitionEvent $event): void
    {
        dd('workflow hit');
        $dossier = $event->getSubject();

        if (!$dossier instanceof Dossier) {
            return;
        }

        $transition = $event->getTransition()->getName();

        $from = implode(',', array_keys($event->getMarking()->getPlaces()));
        $to = $dossier->getStatus()->value;

        // log CRM (source unique)
        $this->audit->log(
            $dossier,
            $transition,
            $from,
            $to
        );

        $vehicle = $dossier->getVehicle();

        if ($transition === 'select_vehicle' && $vehicle) {
            $this->vehicleWorkflow->reserve($vehicle);
            return;
        }

        if ($transition === 'approve_financing') {
            $this->financingService->approve($dossier);
            return;
        }

        if ($transition === 'cancel' && $vehicle) {
            $this->vehicleWorkflow->return($vehicle);
        }
    }
}
