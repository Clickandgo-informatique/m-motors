<?php

namespace App\EventSubscriber;

use App\Entity\Sale;
use App\Entity\Rental;
use App\Enum\VehicleStatus;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

class VehicleStatusSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $em = $args->getObjectManager();

        if ($entity instanceof Sale) {
            $vehicle = $entity->getVehicle();
            $vehicle->setStatus(VehicleStatus::SOLD);
            $em->flush();
        }

        if ($entity instanceof Rental) {
            $vehicle = $entity->getVehicle();
            $vehicle->setStatus(VehicleStatus::RENTED);
            $em->flush();
        }
    }
}
