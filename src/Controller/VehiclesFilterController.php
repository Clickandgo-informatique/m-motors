<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehiclesFilterController extends AbstractController
{
    /**
     * Page principale véhicules avec pagination
     */
    #[Route('/vehicles', name: 'vehicles')]
    public function index(
        VehicleRepository $vehicleRepo,
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

        // Filtres sidebar renvoyés en tant qu'objets
        $brands = $vehicleRepo->getUsedBrands();
        $bodyTypes = $vehicleRepo->getUsedBodyTypes();
        $fuelTypes = $vehicleRepo->getUsedFuelTypes();

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes,
        ]);
    }

    /**
     * Endpoint AJAX pour filtrage dynamique des véhicules
     */
    #[Route('/vehicles/vehicles-search', name: 'vehicles_search', methods: ['POST'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator
    ): Response {
        // Décodage JSON du body
        $data = json_decode($request->getContent(), true) ?: [];
        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = isset($data['page']) && is_numeric($data['page']) ? (int)$data['page'] : 1;

        // QueryBuilder filtré pour pagination
        $query = $vehicleRepo->searchForPaginator($filters, $searchTerm);

        // Pagination KnpPaginator
        $vehicles = $paginator->paginate($query, $page, 20);

        // Retour JSON avec fragments pour injection AJAX
        return $this->json([
            'results' => $this->renderView('vehicles/_vehicles_search_results.html.twig', [
                'vehicles' => $vehicles
            ]),
            'paginationTop' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles
            ]),
            'paginationBottom' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles
            ])
            // Les badges sont maintenant gérés côté JS → plus besoin de filtersSummary ici
        ]);
    }

    /**
     * Endpoint AJAX pour récupérer les filtres sidebar
     */
    #[Route('/vehicles/filters', name: 'vehicles_filters', methods: ['GET'])]
    public function getFilters(
        VehicleRepository $vehicleRepo
    ): Response {
        // Objets pour la sidebar
        $brands = $vehicleRepo->getUsedBrands();
        $bodyTypes = $vehicleRepo->getUsedBodyTypes();
        $fuelTypes = $vehicleRepo->getUsedFuelTypes();

        // Fragment AJAX pour le formulaire de filtres
        return $this->render('vehicles/_vehicles_filters.html.twig', [
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes
        ]);
    }
}
