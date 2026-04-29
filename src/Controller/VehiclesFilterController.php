<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use App\Service\VehicleFilterService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicles')]
class VehiclesFilterController extends AbstractController
{
    /**
     * PAGE PRINCIPALE CATALOGUE
     */
    #[Route('', name: 'vehicles', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepo,
        Request $request,
        PaginatorInterface $paginator,
        VehicleFilterService $filterService
    ): Response {

        $query = $vehicleRepo->getAllVehiclesQueryBuilder();

        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // filtres sidebar
        $brands = $vehicleRepo->getUsedBrands();
        $bodyTypes = $vehicleRepo->getUsedBodyTypes();
        $fuelTypes = $vehicleRepo->getUsedFuelTypes();
        $registrationYears = $vehicleRepo->getRegistrationYears();

        $filters = $request->query->all('filters') ?? [];

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,

            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes,
            'registrationYears' => $registrationYears['years'],
            'registrationYearsMin' => $registrationYears['min'],
            'registrationYearsMax' => $registrationYears['max'],

            'activeFiltersCount' => $filterService->count($filters),
        ]);
    }

    /**
     * SIDEBAR FILTERS (AJAX)
     */
    #[Route('/ajax/filters', name: 'vehicles_ajax_filters', methods: ['GET'])]
    public function getFilters(VehicleRepository $vehicleRepo): Response
    {
        return $this->render('vehicles/_vehicles_filters.html.twig', [
            'brands' => $vehicleRepo->getUsedBrands(),
            'bodyTypes' => $vehicleRepo->getUsedBodyTypes(),
            'fuelTypes' => $vehicleRepo->getUsedFuelTypes(),
            'registrationYears' => $vehicleRepo->getRegistrationYears()['years'],
            'registrationYearsMin' => $vehicleRepo->getRegistrationYears()['min'],
            'registrationYearsMax' => $vehicleRepo->getRegistrationYears()['max'],
        ]);
    }

    /**
     * AJAX SEARCH + AUTOCOMPLETE
     */
    #[Route('/ajax/search', name: 'vehicles_ajax_search', methods: ['GET', 'POST'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator,
        SessionInterface $session,
        VehicleFilterService $filterService
    ): JsonResponse {

        $data = json_decode($request->getContent(), true) ?: $request->query->all();

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = $data['page'] ?? 1;

        // vue
        $view = $filters['view'] ?? $session->get('vehicle_view', 'grid');
        $session->set('vehicle_view', $view);

        // QUERY PRINCIPALE (pagination)
        $query = $filterService->apply(
            $vehicleRepo->getAllVehiclesQueryBuilder(),
            $filters,
            $searchTerm
        );

        $vehicles = $paginator->paginate($query, $page, 20);

        // ✅ AUTOCOMPLETE (indépendant)
        $items = $vehicleRepo->searchForAutocomplete(
            $filters,
            $searchTerm,
            10
        );

        // HTML
        $resultsHtml = $this->renderView(
            $view === 'table'
                ? 'vehicles/_vehicles_table_body.html.twig'
                : 'vehicles/_vehicles_gallery_items.html.twig',
            [
                'vehicles' => $vehicles,
                'view' => $view
            ]
        );

        $paginationHtml = $this->renderView(
            'vehicles/_pagination_info.html.twig',
            ['vehicles' => $vehicles]
        );

        return $this->json([
            'items' => $items,
            'results' => $resultsHtml,
            'paginationTop' => $paginationHtml,
            'paginationBottom' => $paginationHtml,
        ]);
    }
}
