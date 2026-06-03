<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\EventSubscriber\DossierWorkflowSubscriber;
use App\Service\Financing\DossierFinancingService;
use App\Service\VehicleWorkflowService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Event\Event;

class DossierWorkflowSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        // vérifie l'enregistrement du subscriber sur le workflow

        $this->assertSame(
            [
                'workflow.dossier.transition' => 'onTransition',
            ],
            DossierWorkflowSubscriber::getSubscribedEvents()
        );
    }

    public function testSelectVehicleTransition(): void
    {
        // vérifie la réservation du véhicule lors de select_vehicle

        $vehicle = $this->createMock(Vehicle::class);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getVehicle')->willReturn($vehicle);

        $transition = $this->createMock(Transition::class);
        $transition->method('getName')->willReturn('select_vehicle');

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($dossier);
        $event->method('getTransition')->willReturn($transition);

        $vehicleWorkflow = $this->createMock(VehicleWorkflowService::class);
        $vehicleWorkflow
            ->expects($this->once())
            ->method('reserve')
            ->with($vehicle);

        $financingService = $this->createMock(DossierFinancingService::class);
        $financingService
            ->expects($this->never())
            ->method('approve');

        $subscriber = new DossierWorkflowSubscriber(
            $vehicleWorkflow,
            $financingService
        );

        $subscriber->onTransition($event);
    }

    public function testApproveFinancingTransition(): void
    {
        // vérifie l'approbation du financement

        $dossier = $this->createMock(Dossier::class);

        $transition = $this->createMock(Transition::class);
        $transition->method('getName')->willReturn('approve_financing');

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($dossier);
        $event->method('getTransition')->willReturn($transition);

        $vehicleWorkflow = $this->createMock(VehicleWorkflowService::class);
        $vehicleWorkflow
            ->expects($this->never())
            ->method('reserve');

        $financingService = $this->createMock(DossierFinancingService::class);
        $financingService
            ->expects($this->once())
            ->method('approve')
            ->with($dossier);

        $subscriber = new DossierWorkflowSubscriber(
            $vehicleWorkflow,
            $financingService
        );

        $subscriber->onTransition($event);
    }

    public function testCancelTransition(): void
    {
        // vérifie la restitution du véhicule lors d'un cancel

        $vehicle = $this->createMock(Vehicle::class);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getVehicle')->willReturn($vehicle);

        $transition = $this->createMock(Transition::class);
        $transition->method('getName')->willReturn('cancel');

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($dossier);
        $event->method('getTransition')->willReturn($transition);

        $vehicleWorkflow = $this->createMock(VehicleWorkflowService::class);
        $vehicleWorkflow
            ->expects($this->once())
            ->method('return')
            ->with($vehicle);

        $financingService = $this->createMock(DossierFinancingService::class);
        $financingService
            ->expects($this->never())
            ->method('approve');

        $subscriber = new DossierWorkflowSubscriber(
            $vehicleWorkflow,
            $financingService
        );

        $subscriber->onTransition($event);
    }

    public function testIgnoresInvalidSubject(): void
    {
        // vérifie que le subscriber ignore les sujets non Dossier

        $transition = $this->createMock(Transition::class);
        $transition->method('getName')->willReturn('select_vehicle');

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn(new \stdClass());
        $event->method('getTransition')->willReturn($transition);

        $vehicleWorkflow = $this->createMock(VehicleWorkflowService::class);
        $vehicleWorkflow
            ->expects($this->never())
            ->method('reserve');

        $financingService = $this->createMock(DossierFinancingService::class);
        $financingService
            ->expects($this->never())
            ->method('approve');

        $subscriber = new DossierWorkflowSubscriber(
            $vehicleWorkflow,
            $financingService
        );

        $subscriber->onTransition($event);
    }

    public function testSelectVehicleWithNoVehicleDoesNothing(): void
    {
        // vérifie le guard clause quand aucun véhicule n'est lié

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getVehicle')->willReturn(null);

        $transition = $this->createMock(Transition::class);
        $transition->method('getName')->willReturn('select_vehicle');

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($dossier);
        $event->method('getTransition')->willReturn($transition);

        $vehicleWorkflow = $this->createMock(VehicleWorkflowService::class);
        $vehicleWorkflow
            ->expects($this->never())
            ->method('reserve');

        $financingService = $this->createMock(DossierFinancingService::class);
        $financingService
            ->expects($this->never())
            ->method('approve');

        $subscriber = new DossierWorkflowSubscriber(
            $vehicleWorkflow,
            $financingService
        );

        $subscriber->onTransition($event);
    }
}
