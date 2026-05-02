<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller dédié au CRUD des véhicules
 * IMPORTANT : aucune logique frontend / listing ici
 */
#[Route('/vehicles')]
class VehicleController extends AbstractController
{
    /**
     * Création d’un véhicule
     */
    #[Route('/new', name: 'vehicle_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $vehicle = new Vehicle();

        $form = $this->createForm(VehicleFormType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule créé.');

            return $this->redirectToRoute('vehicles_index');
        }

        return $this->render('vehicles/new.html.twig', [
            'form' => $form,
            'title' => 'Créer un véhicule'
        ]);
    }

    /**
     * Edition d’un véhicule
     */
    #[Route('/{id<\d+>}/edit', name: 'vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(VehicleFormType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            $this->addFlash('success', 'Véhicule modifié.');

            return $this->redirectToRoute('vehicles_index');
        }

        return $this->render('vehicles/_vehicle_form.html.twig', [
            'form' => $form,
            'vehicle' => $vehicle,
            'title' => 'Modifier le véhicule'
        ]);
    }

    /**
     * Suppression d’un véhicule
     */
    #[Route('/{id<\d+>}', name: 'vehicle_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        if ($this->isCsrfTokenValid('delete' . $vehicle->getId(), $request->request->get('_token'))) {
            $em->remove($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule supprimé.');
        }

        return $this->redirectToRoute('vehicles_index');
    }
}
