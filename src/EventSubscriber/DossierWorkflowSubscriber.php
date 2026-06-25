<?php

namespace App\EventSubscriber;

use App\Entity\Dossier;
use App\Entity\DossierWorkflowLog;
use App\Service\Financing\DossierFinancingService;
use App\Service\VehicleWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * subscriber des transitions du workflow dossier
 *
 * rôle :
 * - exécuter les effets de bord métier (réservation véhicule, financement, etc.)
 * - tracer chaque transition dans la timeline via dossierworkflowlog
 *
 * important :
 * - ne contient aucune logique de décision métier sur les transitions
 * - doit rester idempotent
 */
class DossierWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private VehicleWorkflowService $vehicleWorkflow,
        private DossierFinancingService $financingService,
        private EntityManagerInterface $em
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.dossier.transition' => 'onTransition',
        ];
    }

    /**
     * appelé à chaque transition du workflow dossier
     */
    public function onTransition(Event $event): void
    {
        $dossier = $event->getSubject();

        if (!$dossier instanceof Dossier) {
            return;
        }

        $transition = $event->getTransition()->getName();

        $vehicle = $dossier->getVehicle();

        // récupération des statuts avant/après (selon marking actuel)
        $fromStatus = implode(',', $event->getMarking()->getPlaces());

        // log de la transition pour alimenter la timeline
        $log = new DossierWorkflowLog();

        $log
            ->setDossier($dossier)
            ->setTransition($transition)
            ->setFromStatus($fromStatus)
            ->setToStatus($fromStatus);

        $this->em->persist($log);

        /*
         * sécurité :
         * si pas de véhicule lié, certaines transitions ne peuvent pas être traitées
         */
        if (!$vehicle && in_array($transition, ['select_vehicle', 'cancel'], true)) {
            $this->em->flush();
            return;
        }

        /*
         * sélection du véhicule
         */
        if ($transition === 'select_vehicle') {
            $this->vehicleWorkflow->reserve($vehicle);
            $this->em->flush();
            return;
        }

        /*
         * validation financement
         */
        if ($transition === 'approve_financing') {
            $this->financingService->approve($dossier);
            $this->em->flush();
            return;
        }

        /*
         * annulation dossier
         */
        if ($transition === 'cancel') {
            $this->vehicleWorkflow->return($vehicle);
            $this->em->flush();
        }
    }
}
