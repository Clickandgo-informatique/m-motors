<?php

namespace App\EventSubscriber;

use App\Entity\Vehicle;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VehicleUsageTypeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Vehicle) {
            return;
        }

        if (!$args->hasChangedField('usageType')) {
            return;
        }

        $old = $args->getOldValue('usageType');
        $new = $args->getNewValue('usageType');

        // ici tu peux ajouter ta logique métier
        // exemple : audit, logs, contraintes, etc.
    }
}