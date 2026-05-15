<?php

namespace App\Service;

use App\Entity\Vehicle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

class VehicleWorkflowService
{
    public function __construct(
        #[Autowire(service: 'state_machine.vehicle_state_machine')]
        private WorkflowInterface $vehicleStateMachine
    ) {}

    public function reserve(Vehicle $vehicle): void
    {
        if ($this->vehicleStateMachine->can($vehicle, 'reserve')) {
            $this->vehicleStateMachine->apply($vehicle, 'reserve');
        }
    }

    public function sell(Vehicle $vehicle): void
    {
        if ($this->vehicleStateMachine->can($vehicle, 'vehicle_sell')) {
            $this->vehicleStateMachine->apply($vehicle, 'vehicle_sell');
        }
    }

    public function rent(Vehicle $vehicle): void
    {
        if ($this->vehicleStateMachine->can($vehicle, 'vehicle_rent')) {
            $this->vehicleStateMachine->apply($vehicle, 'vehicle_rent');
        }
    }

    public function return(Vehicle $vehicle): void
    {
        if ($this->vehicleStateMachine->can($vehicle, 'vehicle_return')) {
            $this->vehicleStateMachine->apply($vehicle, 'vehicle_return');
        }
    }
}
