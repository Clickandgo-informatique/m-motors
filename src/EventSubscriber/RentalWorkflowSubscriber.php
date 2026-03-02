<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Registry;
use Doctrine\ORM\EntityManagerInterface;

class RentalWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Registry $workflowRegistry,
        private EntityManagerInterface $em
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.rental_state_machine.completed.rental_start' => 'onRentalStarted',
            'workflow.rental_state_machine.completed.rental_finish' => 'onRentalFinished',
            'workflow.rental_state_machine.completed.rental_cancel' => 'onRentalCanceled',
        ];
    }

    public function onRentalStarted(CompletedEvent $event): void
    {
        $rental = $event->getSubject();
        $vehicle = $rental->getVehicle();

        $workflow = $this->workflowRegistry->get($vehicle, 'vehicle_state_machine');

        if ($workflow->can($vehicle, 'vehicle_rent')) {
            $workflow->apply($vehicle, 'vehicle_rent');
            $this->em->flush();
        }
    }

    public function onRentalFinished(CompletedEvent $event): void
    {
        $this->makeVehicleAvailable($event);
    }

    public function onRentalCanceled(CompletedEvent $event): void
    {
        $this->makeVehicleAvailable($event);
    }

    private function makeVehicleAvailable(CompletedEvent $event): void
    {
        $rental = $event->getSubject();
        $vehicle = $rental->getVehicle();

        $workflow = $this->workflowRegistry->get($vehicle, 'vehicle_state_machine');

        if ($workflow->can($vehicle, 'vehicle_return')) {
            $workflow->apply($vehicle, 'vehicle_return');
            $this->em->flush();
        }
    }
}
