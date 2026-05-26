<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleFeaturesFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleFeaturesController extends AbstractController
{
    #[Route(
        '/admin/vehicles/features/{id<\d+>}/edit',
        name: 'vehicle_features_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(
            VehicleFeaturesFormType::class,
            $vehicle
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // inutile de persist sur une entité déjà existante
            $em->flush();

            $this->addFlash(
                'message',
                'La liste des options du véhicule a été mise à jour avec succès.'
            );

            return $this->redirectToRoute('vehicles_show', [
                'id' => $vehicle->getId(),
            ]);
        }

        return $this->render(
            'vehicles/_vehicle_features.html.twig',
            [
                'form' => $form->createView(),
                'vehicle' => $vehicle,
            ]
        );
    }
}
