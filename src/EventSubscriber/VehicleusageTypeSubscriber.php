<?php

namespace App\EventSubscriber;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class VehicleUsageTypeSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
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

        if ($old !== $new) {
        
        }
    }
}
