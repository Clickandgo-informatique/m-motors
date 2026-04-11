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
    #[Route('/vehicles/ajax/filters', name: 'vehicles_ajax_filters', methods: ['GET', 'POST'])]
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
     * Gère à la fois autocomplete et mise à jour de la page de cards
     */
    #[Route('/vehicles/ajax/search', name: 'vehicles_ajax_search', methods: ['POST', 'GET'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator,
        SessionInterface $session
    ): JsonResponse {
        // --- Décodage JSON / query string ---
        $data = json_decode($request->getContent(), true) ?: $request->query->all();

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;

        if (isset($filters['q']) && !$searchTerm) {
            $searchTerm = $filters['q'];
        }

        $page = $data['page'] ?? 1;

        // --- Vue (grid / table) ---
        $view = $filters['view'] ?? $session->get('vehicle_view', 'grid');
        $session->set('vehicle_view', $view);

        // --- QueryBuilder avec filtres et recherche ---
        $query = $vehicleRepo->searchForPaginator($filters, $searchTerm);

        // --- Pagination ---
        $vehicles = $paginator->paginate($query, $page, 20);

        // --- Vérifie si c'est une requête autocomplete ---
        $isAutocomplete = $request->query->get('autocomplete') === 'true';

        // --- Construction JSON pour autocomplete ---
        $items = [];
        foreach ($vehicles as $v) {
            $items[] = [
                'id' => $v->getId(),
                'label' => $v->getVehicleModel()->getBrand()->getName() . ' ' . $v->getVehicleModel()->getModel()->getName(),
                'url' => $this->generateUrl('vehicle_edit', ['id' => $v->getId()]) // lien direct pour dropdown
            ];
        }

        // --- Génération HTML des cards (grid ou table) ---
        $resultsHtml = $this->renderView(
            $view === 'table'
                ? 'vehicles/_vehicles_table_body.html.twig'
                : 'vehicles/_vehicles_gallery_items.html.twig',
            ['vehicles' => $vehicles, 'view' => $view]
        );

        $paginationHtml = $this->renderView('vehicles/_pagination_info.html.twig', ['vehicles' => $vehicles]);

        // --- Retour JSON unifié ---
        return $this->json([
            'items' => $items,            // Pour dropdown autocomplete
            'results' => $resultsHtml,    // Pour mise à jour container cards
            'paginationTop' => $paginationHtml,
            'paginationBottom' => $paginationHtml,
        ]);
    }
}
