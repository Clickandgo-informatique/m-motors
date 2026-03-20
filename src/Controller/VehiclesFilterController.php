<?php

namespace App\Controller;

use App\Repository\BodyTypeRepository;
use App\Repository\FuelTypeRepository;
use App\Repository\VehicleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehiclesFilterController extends AbstractController
{
    #[Route('/vehicles', name: 'vehicles')]
    public function index(
        VehicleRepository $vehicleRepo,
        BodyTypeRepository $bodyTypeRepo,
        FuelTypeRepository $fuelTypeRepo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        // QueryBuilder pour tous les véhicules
        $query = $vehicleRepo->getAllVehiclesQueryBuilder();

        // Pagination KnpPaginator
        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // Filtres pour sidebar
        $brands = $vehicleRepo->findBrandNamesWithVehicles();
        $bodyTypes = $bodyTypeRepo->getBodyTypes();
        $fuelTypes = $fuelTypeRepo->getFuelTypes();

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes,
        ]);
    }

    #[Route('/vehicles/vehicles-search', name: 'vehicles_search', methods: ['POST'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator
    ): Response {
        // Décodage JSON du body
        $data = json_decode($request->getContent(), true);

        // S'assurer que $filters est toujours un tableau
        $filters = [];
        if (isset($data['filters']) && is_array($data['filters'])) {
            $filters = $data['filters'];
        }

        // Optionnel : terme de recherche texte pour autocomplete
        $searchTerm = $data['q'] ?? null;
        if (is_string($searchTerm) && trim($searchTerm) === '') {
            $searchTerm = null;
        }

        // QueryBuilder filtré
        $query = $vehicleRepo->searchPaginated($filters, $searchTerm);

        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // Fragment Twig pour injection AJAX
        $html = $this->renderView('vehicles/_vehicles_search_results.html.twig', [
            'vehicles' => $vehicles
        ]);

        return new Response($html);
    }

    #[Route('/vehicles/filters', name: 'vehicles_filters', methods: ['GET'])]
    public function getFilters(
        VehicleRepository $vehicleRepo,
        BodyTypeRepository $bodyTypeRepo,
        FuelTypeRepository $fuelTypeRepo
    ): Response {
        $brands = $vehicleRepo->findBrandNamesWithVehicles();
        $bodyTypes = $bodyTypeRepo->getBodyTypes();
        $fuelTypes = $fuelTypeRepo->getFuelTypes();

        return $this->render('vehicles/_vehicles_filters.html.twig', [
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes
        ]);
    }
}
