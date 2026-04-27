<?php

namespace App\EventSubscriber;

use App\Entity\Dossier;
use App\Enum\DossierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Registry;

class DossierWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Registry $workflowRegistry
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

        /**
         * IMPORTANT :
         * on force le bon workflow explicite
         */
        $workflow = $this->workflowRegistry->get($vehicle, 'vehicle_state_machine');

        $transition = $event->getTransition()->getName();

        // =========================================================
        // SELECT VEHICLE
        // =========================================================
        if ($transition === 'select_vehicle') {

            // available -> reserved
            if ($workflow->can($vehicle, 'reserve')) {
                $workflow->apply($vehicle, 'reserve');
            }

            return;
        }

        // =========================================================
        // APPROVE FINANCING
        // =========================================================
        if ($transition === 'approve_financing') {

            $targetTransition = $dossier->getType() === DossierType::SALE
                ? 'vehicle_sell'
                : 'vehicle_rent';

            // sécurité : workflow guard
            if ($workflow->can($vehicle, $targetTransition)) {
                $workflow->apply($vehicle, $targetTransition);
            }

            return;
        }

        // =========================================================
        // CANCEL DOSSIER
        // =========================================================
        if ($transition === 'cancel') {

            // réservé → available ou rented → available selon état réel
            if ($workflow->can($vehicle, 'cancel_reservation')) {
                $workflow->apply($vehicle, 'cancel_reservation');
            } elseif ($workflow->can($vehicle, 'vehicle_return')) {
                $workflow->apply($vehicle, 'vehicle_return');
            }

            return;
        }

        $this->em->flush();
    }
}
