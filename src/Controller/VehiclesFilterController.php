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
        // Récupération des filtres GET
        $data = $request->query->all();

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;

        $view = $filters['view'] ?? 'grid';

        // Query filtrée
        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        // Pagination (page courante)
        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        // Contexte filtres sidebar
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
        // Merge GET + POST (filters + pagination)
        $data = array_merge(
            $request->query->all(),
            $request->request->all()
        );

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = max(1, (int) ($data['page'] ?? 1));

        $view = $filters['view'] ?? 'grid';

        // Query filtrée
        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        // Pagination AJAX
        $vehicles = $paginator->paginate($query, $page, 10);

        // Contexte filtres (sidebar)
        $context = $this->buildFiltersContext($vehicleRepo);

        // Labels filtres actifs
        $filterLabels = $this->hydrateFilterLabels($vehicleRepo, $filters);

        return $this->json([
            // Liste véhicules
            'list' => $this->renderView('vehicles/_vehicles_list.html.twig', [
                'vehicles' => $vehicles,
                'view' => $view
            ]),

            // Pagination complète (compteur + navigation)
            'pagination' => $this->renderView('vehicles/_pagination.html.twig', [
                'vehicles' => $vehicles
            ]),

            // Sidebar filtres (si re-render complet nécessaire)
            'filters' => $this->renderView('vehicles/_vehicles_filters.html.twig', $context),

            // Résumé filtres actifs
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
        // Recherche autocomplete
        $items = $vehicleRepo->searchForAutocomplete(
            [],
            (string) $request->query->get('q', ''),
            10
        );

        $items = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'url' => $this->generateUrl('vehicle_edit', [
                    'id' => $item['id']
                ])
            ];
        }, $items);

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
        // Render sidebar filters standalone
        return $this->render(
            'vehicles/_vehicles_filters.html.twig',
            $this->buildFiltersContext($vehicleRepo)
        );
    }

    private function hydrateFilterLabels(VehicleRepository $vehicleRepo, array $filters): array
    {
        $labels = [];

        // Marques sélectionnées
        if (!empty($filters['brand'])) {
            $labels['brand'] = $vehicleRepo
                ->createQueryBuilder('v')
                ->select('DISTINCT b.name')
                ->join('v.vehicleModel', 'vm')
                ->join('vm.brand', 'b')
                ->where('b.id IN (:ids)')
                ->setParameter('ids', $filters['brand'])
                ->getQuery()
                ->getSingleColumnResult();
        }

        // Status via enum mapping
        if (!empty($filters['status'])) {
            $map = [];

            foreach (VehicleStatus::cases() as $status) {
                $map[$status->value] = $status->label();
            }

            $labels['status'] = array_map(
                fn($v) => $map[$v] ?? $v,
                $filters['status']
            );
        }

        return $labels;
    }
}
