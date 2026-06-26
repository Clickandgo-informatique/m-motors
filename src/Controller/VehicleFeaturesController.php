<?php

namespace App\Controller;

use App\Entity\Feature;
use App\Entity\VehicleModel;
use App\Form\FeatureFormType;
use App\Form\VehicleFeaturesFormType;
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
        '/api/features',
        name: 'api_features',
        methods: ['GET']
    )]
    public function allFeatures(FeatureRepository $featureRepository): JsonResponse
    {
        $features = $featureRepository->findBy([], ['label' => 'ASC']);

        return $this->json([
            'selected' => [],
            'available' => array_map(
                fn(Feature $feature) => [
                    'id' => $feature->getId(),
                    'label' => $feature->getLabel(),
                ],
                $features
            ),
        ]);
    }
    #[Route(
        '/admin/vehicles/models/features',
        name: 'vehicle_model_features_index',
        methods: ['GET']
    )]
    public function index(
        FeatureRepository $featureRepository
    ): Response {
        $features = $featureRepository->findBy([], ['label' => 'ASC']);

        return $this->render(
            'vehicles_models/_vehicle_model_features.html.twig',
            [
                'features' => $features
            ]
        );
    }

    #[Route(
        '/api/vehicles/models/{id<\d+>}/features',
        name: 'api_vehicle_model_features',
        methods: ['GET']
    )]
    public function apiFeatures(
        VehicleModel $vehicleModel,
        FeatureRepository $featureRepository
    ): JsonResponse {
        $allFeatures = $featureRepository->findBy([], ['label' => 'ASC']);

        return $this->json([
            'selected' => array_map(
                fn(Feature $feature) => [
                    'id' => $feature->getId(),
                    'label' => $feature->getLabel(),
                ],
                $vehicleModel->getFeatures()->toArray()
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
        VehicleModel $vehicleModel,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(
            VehicleFeaturesFormType::class,
            $vehicleModel
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash(
                'message',
                'la liste des options du véhicule a été mise à jour avec succès.'
            );

            return $this->redirectToRoute('vehicles_show', [
                'id' => $vehicleModel->getId(),
            ]);
        }

        return $this->render(
            'vehicles_models/_vehicle_features.html.twig',
            [
                'form' => $form->createView(),
                'vehicle' => $vehicleModel,
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
            $em->flush();

            $this->addFlash(
                'message',
                'l’option a été mise à jour avec succès.'
            );

            return $this->redirectToRoute('vehicle_model_features_index');
        }

        return $this->render(
            'vehicles_models/_feature_form.html.twig',
            [
                'form' => $form->createView(),
                'feature' => $feature,
                'title' => 'modifier une option véhicule'
            ]
        );
    }

    #[Route(
        '/admin/features/new',
        name: 'feature_new',
        methods: ['GET', 'POST']
    )]
    public function newFeature(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $feature = new Feature();

        $form = $this->createForm(
            FeatureFormType::class,
            $feature
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($feature);
            $em->flush();

            $this->addFlash(
                'message',
                'l’option a été enregistrée avec succès.'
            );

            return $this->redirectToRoute('vehicle_model_features_index');
        }

        return $this->render(
            'vehicles_models/_feature_form.html.twig',
            [
                'form' => $form->createView(),
                'feature' => $feature,
                'title' => 'ajouter une option véhicule'
            ]
        );
    }
}
