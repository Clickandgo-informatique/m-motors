<?php

namespace App\Controller;

use App\Enum\VehicleStatus;
use App\Repository\VehicleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/vehicles')]
class VehiclesFilterController extends AbstractController
{
    #[Route('', name: 'vehicles_index', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {

        $filters = $this->normalizeFilters($request->query->all('filters') ?? []);
        $searchTerm = $request->query->get('q', '');

        $page = max(1, $request->query->getInt('page', 1));

        $query = $vehicleRepo->getFilteredQueryBuilder(
            $filters,
            $searchTerm,
            $this->getUser()
        );

        $vehicles = $paginator->paginate($query, $page, 12);

        return $this->render('vehicles/index.html.twig', array_merge(
            $this->buildFiltersContext($vehicleRepo),
            [
                'vehicles' => $vehicles,
                'filters' => $filters,
                'filterLabels' => $this->hydrateFilterLabels($vehicleRepo, $filters),
                'searchTerm' => $searchTerm,
                'priceMin' => $filters['priceMin'] ?? null,
                'priceMax' => $filters['priceMax'] ?? null,
            ]
        ));
    }

    #[Route('/ajax/list', name: 'vehicles_ajax_list', methods: ['GET'])]
    public function list(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator
    ): Response {

        $filters = $this->normalizeFilters($request->query->all('filters') ?? []);
        $searchTerm = $request->query->get('q');

        $page = max(1, $request->query->getInt('page', 1));

        $query = $vehicleRepo->getFilteredQueryBuilder(
            $filters,
            $searchTerm,
            $this->getUser()
        );

        $vehicles = $paginator->paginate($query, $page, 12);

        return $this->json([
            'list' => $this->renderView('vehicles/_vehicles_gallery_items.html.twig', [
                'vehicles' => $vehicles,
            ]),

            'paginationTop' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles,
            ]),

            'paginationBottom' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles,
            ]),

            'filtersSummary' => $this->renderView('vehicles/_filters_summary.html.twig', [
                'filters' => $filters
            ]),

            'priceMin' => $filters['priceMin'] ?? null,
            'priceMax' => $filters['priceMax'] ?? null,
        ]);
    }

    #[Route('/ajax/search', name: 'vehicles_ajax_search', methods: ['GET'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $items = $vehicleRepo->searchForAutocomplete(
            [],
            (string) $request->query->get('q', ''),
            10
        );

        foreach ($items as &$item) {
            $item['url'] = $urlGenerator->generate(
                'vehicle_edit',
                ['id' => $item['id']]
            );
        }

        return $this->json([
            'items' => $items
        ]);
    }

    #[Route('/_sidebar/filters', name: 'vehicles_sidebar_filters', methods: ['GET'])]
    public function sidebarFilters(VehicleRepository $vehicleRepo): Response
    {
        return $this->render(
            'vehicles/_vehicles_filters.html.twig',
            $this->buildFiltersContext($vehicleRepo)
        );
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
        $filters = $filters ?? [];

        $min = $filters['priceMin'] ?? null;
        $max = $filters['priceMax'] ?? null;

        $filters['priceMin'] = is_numeric($min) ? (int) $min : null;
        $filters['priceMax'] = is_numeric($max) ? (int) $max : null;

        if (
            $filters['priceMin'] !== null &&
            $filters['priceMax'] !== null &&
            $filters['priceMin'] > $filters['priceMax']
        ) {
            [$filters['priceMin'], $filters['priceMax']] =
                [$filters['priceMax'], $filters['priceMin']];
        }

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

        //Kilométrage
        $kmMin = $filters['mileageMin'] ?? null;
        $kmMax = $filters['mileageMax'] ?? null;

        $filters['mileageMin'] = is_numeric($kmMin)
            ? (int) $kmMin
            : null;

        $filters['mileageMax'] = is_numeric($kmMax)
            ? (int) $kmMax
            : null;

        if (
            $filters['mileageMin'] !== null &&
            $filters['mileageMax'] !== null &&
            $filters['mileageMin'] > $filters['mileageMax']
        ) {
            [$filters['mileageMin'], $filters['mileageMax']] = [
                $filters['mileageMax'],
                $filters['mileageMin']
            ];
        }

        return $filters;
    }

    private function hydrateFilterLabels(VehicleRepository $vehicleRepo, array $filters): array
    {
        $labels = [];

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
