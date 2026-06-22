<?php

namespace App\Controller;

use App\Entity\Feature;
use App\Entity\Vehicle;
use App\Form\FeatureFormType;
use App\Form\VehicleFeaturesFormType;
use App\Repository\FeatureCategoryRepository;
use App\Repository\FeatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleFeaturesController extends AbstractController
{
    #[Route(
        '/admin/vehicles/models/features/',
        name: 'vehicle_model_features_index',
        methods: ['GET']
    )]
    public function index(FeatureRepository $featureRepository, FeatureCategoryRepository $featureCategoryRepo)
    {

        $features = $featureRepository->findBy([], ['label' => 'ASC']);
        $featuresCategories = $featureCategoryRepo->findBy([], ['label' => 'ASC']);

        return $this->render('vehicles_models/_vehicle_model_features.html.twig', ['features' => $features, 'featuresCategories' => $featuresCategories]);
    }

    #[Route(
        '/api/vehicles/models/{id<\d+>}/features',
        name: 'api_vehicle_model_features',
        methods: ['GET']
    )]
    public function apiFeatures(
        Vehicle $vehicle,
        FeatureRepository $featureRepository
    ): JsonResponse {

        $allFeatures = $featureRepository->findBy([], [
            'label' => 'ASC'
        ]);

        return $this->json([
            'selected' => array_map(
                fn(Feature $feature) => [
                    'id' => $feature->getId(),
                    'label' => $feature->getLabel(),
                ],
                $vehicle->getFeatures()->toArray()
            ),
            'available' => array_map(
                fn(Feature $feature) => [
                    'id' => $feature->getId(),
                    'label' => $feature->getLabel(),
                ],
                $allFeatures
            ),
        ]);
    }

    #[Route(
        '/admin/vehicles/models/features/{id<\d+>}/edit',
        name: 'vehicle_features_edit',
        methods: ['GET', 'POST']
    )]
    public function editVehicleFeatures(
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
            'vehicles_models/_vehicle_features.html.twig',
            [
                'form' => $form->createView(),
                'vehicle' => $vehicle,
            ]
        );
    }
    #[Route(
        '/admin/features/{id<\d+>}/edit',
        name: 'feature_edit',
        methods: ['GET', 'POST']
    )]
    public function editFeatures(
        Request $request,
        Feature $feature,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(
            FeatureFormType::class,
            $feature
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // inutile de persist sur une entité déjà existante
            $em->flush();

            $this->addFlash(
                'message',
                'La liste des options a été mise à jour avec succès.'
            );

            return $this->redirectToRoute('vehicle_model_features_index');
        }

        return $this->render(
            'vehicles_models/_feature_form.html.twig',
            [
                'form' => $form->createView(),
                'feature' => $feature,
                'title' => 'Modifier une option véhicule'
            ]
        );
    }
    public function newFeature(
        Request $request,
        Feature $feature,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(
            FeatureFormType::class,
            $feature
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feature = new Feature();

            $em->persist($feature);
            $em->flush();

            $this->addFlash(
                'message',
                'L\'option a été enregistrée avec succès.'
            );

            return $this->redirectToRoute('vehicle_model_features_index');
        }

        return $this->render(
            'vehicles_models/_feature_form.html.twig',
            [
                'form' => $form->createView(),
                'feature' => $feature,
                'title' => 'Ajouter une option véhicule'
            ]
        );
    }
}
