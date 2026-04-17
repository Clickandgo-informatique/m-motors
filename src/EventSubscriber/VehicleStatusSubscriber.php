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

        // =========================
        // SALE → VEHICLE SOLD
        // =========================
        if ($entity instanceof Sale) {

            $vehicle = $entity->getVehicle();

            if (!$vehicle) {
                return;
            }

            $vehicle->setStatus(VehicleStatus::SOLD);

            // ⚠️ PAS DE FLUSH ICI
            $em->persist($vehicle);
        }

        // =========================
        // RENTAL → VEHICLE RENTED
        // =========================
        if ($entity instanceof Rental) {

            $vehicle = $entity->getVehicle();

            if (!$vehicle) {
                return;
            }

            $vehicle->setStatus(VehicleStatus::RENTED);

            // ⚠️ PAS DE FLUSH ICI
            $em->persist($vehicle);
        }
    }
}
