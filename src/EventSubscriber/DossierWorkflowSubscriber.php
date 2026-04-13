<?php

namespace App\EventSubscriber;

use App\Entity\Dossier;
use App\Enum\DossierStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class DossierWorkflowSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.dossier.completed.complete' => 'onCompleted',
            'workflow.dossier.completed.cancel_from_draft' => 'onCancelled',
            'workflow.dossier.completed.cancel_from_progress' => 'onCancelled',
        ];
    }

    public function onCompleted(Event $event): void
    {
        /** @var Dossier $dossier */
        $dossier = $event->getSubject();

        $dossier->setCompletedAt(new \DateTimeImmutable());
    }

    public function onCancelled(Event $event): void
    {
        /** @var Dossier $dossier */
        $dossier = $event->getSubject();

        $dossier->setCancelledAt(new \DateTimeImmutable());
    }
}
