<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class VehiclesFilterController extends AbstractController
{
    /**
     * Page principale véhicules avec pagination
     */
    #[Route('/vehicles', name: 'vehicles', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        // QueryBuilder pour tous les véhicules
        $query = $vehicleRepo->getAllVehiclesQueryBuilder();

        // Pagination
        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // Filtres pour la sidebar
        $brands = $vehicleRepo->getUsedBrands();
        $bodyTypes = $vehicleRepo->getUsedBodyTypes();
        $fuelTypes = $vehicleRepo->getUsedFuelTypes();
        $registrationYears = $vehicleRepo->getRegistrationYears();

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes,
            'registrationYears' => $registrationYears['years'],
            'registrationYearsMin' => $registrationYears['min'],
            'registrationYearsMax' => $registrationYears['max'],
        ]);
    }

    /**
     * Endpoint AJAX pour récupérer les filtres sidebar
     */
    #[Route('/vehicles/ajax/filters', name: 'vehicles_ajax_filters', methods: ['GET'])]
    public function getFilters(VehicleRepository $vehicleRepo): Response
    {
        $brands = $vehicleRepo->getUsedBrands();
        $bodyTypes = $vehicleRepo->getUsedBodyTypes();
        $fuelTypes = $vehicleRepo->getUsedFuelTypes();
        $registrationYears = $vehicleRepo->getRegistrationYears();

        return $this->render('vehicles/_vehicles_filters.html.twig', [
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes,
            'registrationYears' => $registrationYears['years'],
            'registrationYearsMin' => $registrationYears['min'],
            'registrationYearsMax' => $registrationYears['max'],
        ]);
    }

    /**
     * Endpoint AJAX pour recherche / filtres dynamiques
     */
    #[Route('/vehicles/ajax/search', name: 'vehicles_ajax_search', methods: ['POST'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator,
        SessionInterface $session // nécessaire pour stocker la vue
    ): JsonResponse {
        // --- Décodage du JSON envoyé par le JS ---
        $data = json_decode($request->getContent(), true) ?: [];
        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = isset($data['page']) ? (int)$data['page'] : 1;

        // --- Détermination du mode view ---
        $view = $filters['view'] ?? $session->get('vehicle_view', 'grid');
        $session->set('vehicle_view', $view); // persiste la vue côté session

        // --- Construction de la requête avec filtres et recherche ---
        $query = $vehicleRepo->searchForPaginator($filters, $searchTerm);

        // --- Pagination ---
        $vehicles = $paginator->paginate($query, $page, 20);

        // --- Rendu conditionnel selon view ---
        $resultsHtml = $this->renderView(
            $view === 'table'
                ? 'vehicles/_vehicles_table_body.html.twig'   // template table
                : 'vehicles/_vehicles_gallery_items.html.twig', // template grid
            ['vehicles' => $vehicles, 'view' => $view]
        );

        $paginationHtml = $this->renderView('vehicles/_pagination_info.html.twig', ['vehicles' => $vehicles]);

        // --- Retour JSON ---
        return $this->json([
            'results' => $resultsHtml,
            'paginationTop' => $paginationHtml,
            'paginationBottom' => $paginationHtml
        ]);
    }
}
