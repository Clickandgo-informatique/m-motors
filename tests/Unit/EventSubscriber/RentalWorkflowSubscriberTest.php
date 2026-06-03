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
        $workflow->method('can')->with($vehicle, 'vehicle_rent')->willReturn(true);

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

    public function testRentalFinishedMakesVehicleAvailable(): void
    {
        // vérifie la libération du véhicule après fin de location

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('can')->with($vehicle, 'vehicle_return')->willReturn(true);

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

    public function testRentalCanceledMakesVehicleAvailable(): void
    {
        // vérifie la libération du véhicule lors d'une annulation

        $vehicle = $this->createMock(Vehicle::class);

        $rental = $this->createMockRental($vehicle);

        $event = $this->createMock(Event::class);
        $event->method('getSubject')->willReturn($rental);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('can')->with($vehicle, 'vehicle_return')->willReturn(true);

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
        $rental = new class($vehicle) {
            public function __construct(private ?Vehicle $vehicle) {}

            public function getVehicle(): ?Vehicle
            {
                return $this->vehicle;
            }
        };

        return $rental;
    }
}
