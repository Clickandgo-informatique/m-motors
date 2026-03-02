<?php

namespace App\Controller;

use App\Entity\Rental;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry;

#[Route('/rental')]
class RentalController extends AbstractController
{
    #[Route('/{id}/start', name: 'rental_start')]
    public function start(
        Rental $rental,
        Registry $workflowRegistry,
        EntityManagerInterface $em
    ): Response {
        $workflow = $workflowRegistry->get($rental, 'rental_state_machine');

        if ($workflow->can($rental, 'rental_start')) {
            $workflow->apply($rental, 'rental_start');
            $em->flush();
            $this->addFlash('success', 'Location démarrée.');
        } else {
            $this->addFlash('error', 'Transition impossible.');
        }

        return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
    }

    #[Route('/{id}/finish', name: 'rental_finish')]
    public function finish(
        Rental $rental,
        Registry $workflowRegistry,
        EntityManagerInterface $em
    ): Response {
        $workflow = $workflowRegistry->get($rental, 'rental_state_machine');

        if ($workflow->can($rental, 'rental_finish')) {
            $workflow->apply($rental, 'rental_finish');
            $em->flush();
            $this->addFlash('success', 'Location terminée.');
        } else {
            $this->addFlash('error', 'Transition impossible.');
        }

        return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
    }

    #[Route('/{id}/cancel', name: 'rental_cancel')]
    public function cancel(
        Rental $rental,
        Registry $workflowRegistry,
        EntityManagerInterface $em
    ): Response {
        $workflow = $workflowRegistry->get($rental, 'rental_state_machine');

        if ($workflow->can($rental, 'rental_cancel')) {
            $workflow->apply($rental, 'rental_cancel');
            $em->flush();
            $this->addFlash('success', 'Location annulée.');
        } else {
            $this->addFlash('error', 'Transition impossible.');
        }

        return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
    }
}