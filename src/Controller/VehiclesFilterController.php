<?php

namespace App\Controller;

use App\Enum\VehicleStatus;
use App\Repository\VehicleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicles')]
class VehiclesFilterController extends AbstractController
{
    #[Route('', name: 'vehicles_index', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $data = $request->query->all();

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;

        $view = $filters['view'] ?? 'grid';

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('vehicles/index.html.twig', array_merge([
            'vehicles' => $vehicles,
            'view' => $view
        ], $this->buildFiltersContext($vehicleRepo)));
    }

    #[Route('/ajax/list', name: 'vehicles_ajax_list', methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator
    ): Response {

        // =========================
        // SAFE INPUT PARSING
        // =========================
        $data = array_merge(
            $request->query->all(),
            $request->request->all()
        );

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = max(1, (int) ($data['page'] ?? 1));

        // SAFE VIEW fallback
        $view = $filters['view'] ?? 'grid';

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate($query, $page, 10);

        $context = $this->buildFiltersContext($vehicleRepo);

        // =========================
        // LIST RENDER
        // =========================
        $listHtml = $this->renderView('vehicles/_vehicles_list.html.twig', [
            'vehicles' => $vehicles,
            'view' => $view
        ]);

        // =========================
        // FILTERS RENDER
        // =========================
        $filtersHtml = $this->renderView('vehicles/_vehicles_filters.html.twig', $context);

        // =========================
        // PAGINATION (safe split)
        // =========================
        $paginationHtml = $this->renderView('vehicles/_pagination_info.html.twig', [
            'vehicles' => $vehicles
        ]);

        return $this->json([
            'list' => $listHtml,
            'filters' => $filtersHtml,
            'pagination_top' => $paginationHtml,
            'pagination_bottom' => $paginationHtml,
            'view' => $view
        ]);
    }

    #[Route('/ajax/search', name: 'vehicles_ajax_search', methods: ['GET'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo
    ): Response {

        $q = $request->query->get('q', '');

        $items = $vehicleRepo->searchForAutocomplete([], $q, 10);

        return $this->json([
            'items' => $items
        ]);
    }

    private function buildFiltersContext(VehicleRepository $vehicleRepo): array
    {
        $years = $vehicleRepo->getRegistrationYears();

        return [
            'brands' => $vehicleRepo->getUsedBrands(),
            'bodyTypes' => $vehicleRepo->getUsedBodyTypes(),
            'fuelTypes' => $vehicleRepo->getUsedFuelTypes(),
            'registrationYears' => $years['years'] ?? [],
            'registrationYearsMin' => $years['min'] ?? null,
            'registrationYearsMax' => $years['max'] ?? null,
            'statuses' => $vehicleRepo->getStatuses()
        ];
    }

    #[Route('/_sidebar/filters', name: 'vehicles_sidebar_filters')]
    public function sidebarFilters(
        VehicleRepository $vehicleRepo
    ): Response {

        return $this->render(
            'vehicles/_vehicles_filters.html.twig',
            $this->buildFiltersContext($vehicleRepo)
        );
    }
}
