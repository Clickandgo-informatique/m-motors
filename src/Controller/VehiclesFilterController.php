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
        // Récupération filtres URL
        $data = $request->query->all();

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $view = $filters['view'] ?? 'grid';

        // Query principale
        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $context = $this->buildFiltersContext($vehicleRepo);

        return $this->render('vehicles/index.html.twig', array_merge([
            'vehicles' => $vehicles,
            'view' => $view
        ], $context));
    }

    #[Route('/ajax/list', name: 'vehicles_ajax_list', methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator
    ): Response {
        // Support GET + POST
        $data = $request->request->all();

        if (empty($data)) {
            $data = $request->query->all();
        }

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = max(1, (int) ($data['page'] ?? 1));

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate($query, $page, 10);

        $context = $this->buildFiltersContext($vehicleRepo);

        $listHtml = $this->renderView('vehicles/_vehicles_list.html.twig', [
            'vehicles' => $vehicles,
            'view' => $filters['view'] ?? 'grid'
        ]);

        $pagination = $this->renderView('vehicles/_pagination_info.html.twig', [
            'vehicles' => $vehicles
        ]);

        return $this->json([
            'list' => $listHtml,
            'pagination_top' => $pagination,
            'pagination_bottom' => $pagination,
            'filters' => $this->renderView('vehicles/_vehicles_filters.html.twig', $context)
        ]);
    }

    #[Route('/ajax/search', name: 'vehicles_ajax_search', methods: ['GET'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo
    ): Response {
        $q = $request->query->get('q');

        $items = $vehicleRepo->searchForAutocomplete([], $q, 10);

        return $this->json([
            'items' => $items
        ]);
    }

    /**
     * Contexte unique filtres + sliders
     */
  private function buildFiltersContext(
    VehicleRepository $vehicleRepo
): array {
    $years = $vehicleRepo->getRegistrationYears();

    $yearMin = isset($years['min']) ? (int) $years['min'] : null;
    $yearMax = isset($years['max']) ? (int) $years['max'] : null;

    // =========================
    // STATUS ENUM
    // =========================
    $statuses = [];

    foreach (VehicleStatus::cases() as $status) {
        $statuses[] = [
            'value' => $status->value,
            'label' => $status->label(),
        ];
    }

    return [
        'brands' => $vehicleRepo->getUsedBrands(),
        'bodyTypes' => $vehicleRepo->getUsedBodyTypes(),
        'fuelTypes' => $vehicleRepo->getUsedFuelTypes(),
        'registrationYears' => $years['years'] ?? [],
        'registrationYearsMin' => $yearMin,
        'registrationYearsMax' => $yearMax,      
        'statuses' => $statuses,
    ];
}
}
