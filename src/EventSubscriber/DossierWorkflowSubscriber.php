<?php

namespace App\EventSubscriber;

use App\Entity\Dossier;
use App\Service\Financing\DossierFinancingService;
use App\Service\VehicleWorkflowService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Subscriber des transitions du workflow dossier.
 *
 * Règle :
 * - aucune logique de transition ici
 * - uniquement des effets de bord métier
 * - doit rester idempotent (appel multiple sans effet négatif)
 */
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

        $transition = $event->getTransition()->getName();

        $vehicle = $dossier->getVehicle();

        /*
         * Sécurité :
         * si pas de véhicule lié, aucune action métier possible
         */
        if (!$vehicle && in_array($transition, ['select_vehicle', 'cancel'], true)) {
            return;
        }

        /*
         * SELECT VEHICLE
         */
        if ($transition === 'select_vehicle') {
            $this->vehicleWorkflow->reserve($vehicle);
            return;
        }

        /*
         * FINANCING APPROVAL
         */
        if ($transition === 'approve_financing') {
            $this->financingService->approve($dossier);
            return;
        }

        /*
         * CANCEL DOSSIER
         */
        if ($transition === 'cancel') {
            $this->vehicleWorkflow->return($vehicle);
        }
    }
}
