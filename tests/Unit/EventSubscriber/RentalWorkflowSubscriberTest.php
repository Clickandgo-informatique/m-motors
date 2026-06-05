<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Vehicle;
use App\EventSubscriber\RentalWorkflowSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

class RentalWorkflowSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        // vérifie l'enregistrement des événements workflow

        $this->assertSame(
            [
                'workflow.rental_state_machine.completed.rental_start' => 'onRentalStarted',
                'workflow.rental_state_machine.completed.rental_finish' => 'onRentalFinished',
                'workflow.rental_state_machine.completed.rental_cancel' => 'onRentalCanceled',
            ],
            RentalWorkflowSubscriber::getSubscribedEvents()
        );
    }

    public function testRentalStartedAppliesVehicleRent(): void
    {
        // vérifie que le workflow passe le véhicule en mode location

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('can')
            ->with($vehicle, 'vehicle_rent')
            ->willReturn(true);

        $workflow->expects($this->once())
            ->method('apply')
            ->with($vehicle, 'vehicle_rent');

        $registry = $this->createMock(Registry::class);
        $registry->method('get')
            ->with($vehicle, 'vehicle')
            ->willReturn($workflow);

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalStarted($event);
    }

    public function testRentalStartedDoesNothingWhenTransitionNotAllowed(): void
    {
        // vérifie qu'aucune transition n'est appliquée si le workflow la refuse

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);

        $workflow->expects($this->once())
            ->method('can')
            ->with($vehicle, 'vehicle_rent')
            ->willReturn(false);

        $workflow->expects($this->never())
            ->method('apply');

        $registry = $this->createMock(Registry::class);

        $registry->expects($this->once())
            ->method('get')
            ->with($vehicle, 'vehicle')
            ->willReturn($workflow);

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalStarted($event);
    }

    public function testRentalFinishedMakesVehicleAvailable(): void
    {
        // vérifie la libération du véhicule après fin de location

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('can')
            ->with($vehicle, 'vehicle_return')
            ->willReturn(true);

        $workflow->expects($this->once())
            ->method('apply')
            ->with($vehicle, 'vehicle_return');

        $registry = $this->createMock(Registry::class);
        $registry->method('get')
            ->with($vehicle, 'vehicle')
            ->willReturn($workflow);

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalFinished($event);
    }

    public function testRentalFinishedDoesNothingWhenReturnTransitionNotAllowed(): void
    {
        // vérifie qu'aucune transition n'est appliquée si le retour n'est pas autorisé

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);

        $workflow->expects($this->once())
            ->method('can')
            ->with($vehicle, 'vehicle_return')
            ->willReturn(false);

        $workflow->expects($this->never())
            ->method('apply');

        $registry = $this->createMock(Registry::class);

        $registry->expects($this->once())
            ->method('get')
            ->with($vehicle, 'vehicle')
            ->willReturn($workflow);

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalFinished($event);
    }

    public function testRentalCanceledMakesVehicleAvailable(): void
    {
        // vérifie la libération du véhicule lors d'une annulation

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('can')
            ->with($vehicle, 'vehicle_return')
            ->willReturn(true);

        $workflow->expects($this->once())
            ->method('apply')
            ->with($vehicle, 'vehicle_return');

        $registry = $this->createMock(Registry::class);
        $registry->method('get')
            ->with($vehicle, 'vehicle')
            ->willReturn($workflow);

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalCanceled($event);
    }

    public function testRentalCanceledDoesNothingWhenReturnTransitionNotAllowed(): void
    {
        // vérifie qu'aucune transition n'est appliquée lors d'une annulation si le retour est refusé

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);

        $workflow->expects($this->once())
            ->method('can')
            ->with($vehicle, 'vehicle_return')
            ->willReturn(false);

        $workflow->expects($this->never())
            ->method('apply');

        $registry = $this->createMock(Registry::class);

        $registry->expects($this->once())
            ->method('get')
            ->with($vehicle, 'vehicle')
            ->willReturn($workflow);

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalCanceled($event);
    }

    public function testIgnoresInvalidVehicle(): void
    {
        // vérifie que le subscriber ignore les sujets invalides

        $rental = $this->createMockRental(null);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $registry = $this->createMock(Registry::class);

        $registry->expects($this->never())
            ->method('get');

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalStarted($event);
    }

    private function createMockRental(?Vehicle $vehicle): object
    {
        return new class($vehicle) {
            public function __construct(private ?Vehicle $vehicle) {}

            public function getVehicle(): ?Vehicle
            {
                return $this->vehicle;
            }
        };
    }
    public function testRentalFinishedIgnoresInvalidVehicle(): void
    {
        // vérifie qu'aucun workflow n'est chargé si le véhicule est invalide

        $rental = $this->createMockRental(null);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $registry = $this->createMock(Registry::class);

        $registry->expects($this->never())
            ->method('get');

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalFinished($event);
    }
    public function testRentalCanceledIgnoresInvalidVehicle(): void
    {
        // vérifie qu'aucun workflow n'est chargé si le véhicule est invalide

        $rental = $this->createMockRental(null);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $registry = $this->createMock(Registry::class);

        $registry->expects($this->never())
            ->method('get');

        $subscriber = new RentalWorkflowSubscriber(
            $registry,
            $this->createMock(EntityManagerInterface::class)
        );

        $subscriber->onRentalCanceled($event);
    }
}
