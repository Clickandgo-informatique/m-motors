<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use App\Enum\VehicleStatus;
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

        $context = $this->buildFiltersContext($vehicleRepo);

        return $this->render('vehicles/index.html.twig', array_merge($context, [
            'vehicles' => $vehicles,
            'view' => $view,
            'filters' => $filters,
            'filterLabels' => $this->hydrateFilterLabels($vehicleRepo, $filters),
            'searchTerm' => $searchTerm,
        ]));
    }

    #[Route('/ajax/list', name: 'vehicles_ajax_list', methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator
    ): Response {
        $data = array_merge(
            $request->query->all(),
            $request->request->all()
        );

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = max(1, (int) ($data['page'] ?? 1));

        $view = $filters['view'] ?? 'grid';

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate($query, $page, 10);

        $context = $this->buildFiltersContext($vehicleRepo);
        $filterLabels = $this->hydrateFilterLabels($vehicleRepo, $filters);

        return $this->json([
            'list' => $this->renderView('vehicles/_vehicles_list.html.twig', [
                'vehicles' => $vehicles,
                'view' => $view
            ]),
            'pagination_top' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles
            ]),
            'pagination_bottom' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles
            ]),
            'filters' => $this->renderView('vehicles/_vehicles_filters.html.twig', $context),
            'filtersSummary' => $this->renderView('vehicles/_filters_summary.html.twig', [
                'filters' => $filters,
                'filterLabels' => $filterLabels
            ]),
        ]);
    }

    #[Route('/ajax/search', name: 'vehicles_ajax_search', methods: ['GET'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo
    ): Response {
        return $this->json([
            'items' => $vehicleRepo->searchForAutocomplete(
                [],
                (string) $request->query->get('q', ''),
                10
            )
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

            'statuses' => array_map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ], VehicleStatus::cases()),
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

    private function hydrateFilterLabels(VehicleRepository $repo, array $filters): array
    {
        $labels = [];

        if (!empty($filters['brand'])) {
            $labels['brand'] = $repo->getBrandNamesByIds($filters['brand']);
        }

        if (!empty($filters['status'])) {
            $map = [];
            foreach (VehicleStatus::cases() as $status) {
                $map[$status->value] = $status->label();
            }

            $labels['status'] = array_map(fn($v) => $map[$v] ?? $v, $filters['status']);
        }

        return $labels;
    }
}
