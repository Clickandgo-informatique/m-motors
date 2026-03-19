<?php

namespace App\Controller;

use App\Repository\BodyTypeRepository;
use App\Repository\BrandRepository;
use App\Repository\FuelTypeRepository;
use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehiclesFilterController extends AbstractController
{
    //Afficher les différents filtres disponibles concernant les véhicules

    #[Route(path: '/vehicles/filters', name: 'vehicles_filters', methods: ['GET'])]
    public function getFilters(
        BrandRepository $brandRepo,
        BodyTypeRepository $bodyTypeRepo,
        FuelTypeRepository $fuelTypeRepo
    ): Response {
        $brands = $brandRepo->getBrands();
        $bodyTypes = $bodyTypeRepo->getBodyTypes();
        $fuelTypes = $fuelTypeRepo->getFuelTypes();

        return $this->render('vehicles/_vehicles_filters.html.twig', [
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes
        ]);
    }

    //Filtres dynamiques pour recherche utilisateur concernant les véhicules disponibles
    #[Route(path: '/vehicles/vehicles-search', name: 'vehicles_search', methods: ['POST'])]
    public function search(Request $request, VehicleRepository $repo): JsonResponse
    {
        // Récupération du JSON envoyé par fetch()
        $data = json_decode($request->getContent(), true);
        $filters = $data['filters'] ?? [];

        // Récupération des véhicules filtrés
        $vehicles = $repo->findByFilters($filters);

        // Construction d'un tableau propre pour éviter les références circulaires
        $results = [];

        foreach ($vehicles as $vehicle) {
            $model = $vehicle->getVehicleModel();

            $results[] = [
                'id'    => $vehicle->getId(),
                'model' => $model->getModel(),
                'brand' => $model->getBrand()->getName(),
                'fuel'  => $model->getFuelType(),
                'year'  => $vehicle->getYear(),
                'mileage' => $vehicle->getMileage()
            ];
        }

        return new JsonResponse($results);
    }
}
