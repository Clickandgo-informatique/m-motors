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
        $filters = $this->normalizeFilters($data['filters'] ?? []);
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

            // état sliders
            'priceMin' => $filters['priceMin'] ?? null,
            'priceMax' => $filters['priceMax'] ?? null,
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

        $filters = $this->normalizeFilters($data['filters'] ?? []);
        $searchTerm = $data['q'] ?? null;
        $page = max(1, (int) ($data['page'] ?? 1));

        $view = $filters['view'] ?? 'grid';

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate($query, $page, 10);

        $context = $this->buildFiltersContext($vehicleRepo);

        return $this->json([
            'list' => $this->renderView('vehicles/_vehicles_list.html.twig', [
                'vehicles' => $vehicles,
                'view' => $view
            ]),

            'pagination' => $this->renderView('vehicles/_pagination.html.twig', [
                'vehicles' => $vehicles
            ]),

            'filters' => $this->renderView('vehicles/_vehicles_filters.html.twig', $context),

            'filtersSummary' => $this->renderView('vehicles/_filters_summary.html.twig', [
                'filters' => $filters
            ]),

            // état sliders côté front
            'priceMin' => $filters['priceMin'] ?? null,
            'priceMax' => $filters['priceMax'] ?? null,
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

            'registrationYearsMin' => $years['min'] ?? null,
            'registrationYearsMax' => $years['max'] ?? null,

            'statuses' => array_map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ], VehicleStatus::cases()),

            'priceMinGlobal' => $vehicleRepo->getMinPrice(),
            'priceMaxGlobal' => $vehicleRepo->getMaxPrice(),
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        // PRICE
        $min = $filters['priceMin'] ?? null;
        $max = $filters['priceMax'] ?? null;

        $filters['priceMin'] = (is_numeric($min)) ? (int) $min : null;
        $filters['priceMax'] = (is_numeric($max)) ? (int) $max : null;

        if ($filters['priceMin'] !== null && $filters['priceMax'] !== null && $filters['priceMin'] > $filters['priceMax']) {
            [$filters['priceMin'], $filters['priceMax']] = [$filters['priceMax'], $filters['priceMin']];
        }

        // YEARS (IMPORTANT FIX BUG -1 / 0)
        $yMin = $filters['registrationYearMin'] ?? null;
        $yMax = $filters['registrationYearMax'] ?? null;

        $filters['registrationYearMin'] = (is_numeric($yMin) && $yMin > 1900) ? (int) $yMin : null;
        $filters['registrationYearMax'] = (is_numeric($yMax) && $yMax > 1900) ? (int) $yMax : null;

        if (
            $filters['registrationYearMin'] !== null &&
            $filters['registrationYearMax'] !== null &&
            $filters['registrationYearMin'] > $filters['registrationYearMax']
        ) {
            [$filters['registrationYearMin'], $filters['registrationYearMax']] =
                [$filters['registrationYearMax'], $filters['registrationYearMin']];
        }

        return $filters;
    }

    /**
     * Transforme les IDs de filtres en labels lisibles pour l'UI
     */
    private function hydrateFilterLabels(VehicleRepository $vehicleRepo, array $filters): array
    {
        $labels = [];

        // Marques
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

        // Statuts
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
    /**
     * Fragment utilisé pour recharger uniquement la sidebar filtres
     * via AJAX / Symfony fragment renderer
     */
    #[Route('/_sidebar/filters', name: 'vehicles_sidebar_filters', methods: ['GET'])]
    public function sidebarFilters(VehicleRepository $vehicleRepo): Response
    {
        return $this->render('vehicles/_vehicles_filters.html.twig', $this->buildFiltersContext($vehicleRepo));
    }
}
