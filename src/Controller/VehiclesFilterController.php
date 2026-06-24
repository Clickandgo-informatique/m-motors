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

        // normalisation des filtres GET
        $filters = $this->normalizeFilters($request->query->all('filters') ?? []);
        $searchTerm = $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));

        // détection rôle admin/manager
        $isAdminOrManager =
            $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_MANAGER') ||
            $this->isGranted('ROLE_SUPER_ADMIN');

        // construction requête véhicules
        $query = $vehicleRepo->getFilteredQueryBuilder(
            $filters,
            $searchTerm,
            $this->getUser(),
            availableOnly: !$isAdminOrManager
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

        // filtres AJAX
        $filters = $this->normalizeFilters($request->query->all('filters') ?? []);
        $searchTerm = $request->query->get('q');
        $page = max(1, $request->query->getInt('page', 1));

        // détection rôle admin/manager
        $isAdminOrManager =
            $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_MANAGER') ||
            $this->isGranted('ROLE_SUPER_ADMIN');

        // requête véhicules AJAX
        $query = $vehicleRepo->getFilteredQueryBuilder(
            $filters,
            $searchTerm,
            $this->getUser(),
            availableOnly: !$isAdminOrManager
        );

        $vehicles = $paginator->paginate($query, $page, 12);

        return $this->json([
            // liste véhicules HTML
            'list' => $this->renderView('vehicles/_vehicles_gallery_items.html.twig', [
                'vehicles' => $vehicles,
            ]),

            // pagination haut
            'paginationTop' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles,
            ]),

            // pagination bas
            'paginationBottom' => $this->renderView('vehicles/_pagination_info.html.twig', [
                'vehicles' => $vehicles,
            ]),

            // résumé filtres actifs
            'filtersSummary' => $this->renderView('vehicles/_filters_summary.html.twig', [
                'filters' => $filters
            ]),

            // bornes prix
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

        // autocomplete véhicules (attention: pas filtré actuellement)
        $items = $vehicleRepo->searchForAutocomplete(
            [],
            (string) $request->query->get('q', ''),
            10
        );

        // ajout URL admin édition
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
        // contexte filtres sidebar
        return $this->render(
            'vehicles/_vehicles_filters.html.twig',
            $this->buildFiltersContext($vehicleRepo)
        );
    }

    private function buildFiltersContext(VehicleRepository $vehicleRepo): array
    {
        // dataset filtres dynamiques
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

        // prix
        $filters['priceMin'] = is_numeric($filters['priceMin'] ?? null)
            ? (int) $filters['priceMin']
            : null;

        $filters['priceMax'] = is_numeric($filters['priceMax'] ?? null)
            ? (int) $filters['priceMax']
            : null;

        // correction inversion prix
        if ($filters['priceMin'] && $filters['priceMax'] && $filters['priceMin'] > $filters['priceMax']) {
            [$filters['priceMin'], $filters['priceMax']] = [
                $filters['priceMax'],
                $filters['priceMin']
            ];
        }

        // années immatriculation
        $filters['registrationYearMin'] = is_numeric($filters['registrationYearMin'] ?? null)
            ? (int) $filters['registrationYearMin']
            : null;

        $filters['registrationYearMax'] = is_numeric($filters['registrationYearMax'] ?? null)
            ? (int) $filters['registrationYearMax']
            : null;

        // kilométrage
        $filters['mileageMin'] = is_numeric($filters['mileageMin'] ?? null)
            ? (int) $filters['mileageMin']
            : null;

        $filters['mileageMax'] = is_numeric($filters['mileageMax'] ?? null)
            ? (int) $filters['mileageMax']
            : null;

        return $filters;
    }

    private function hydrateFilterLabels(VehicleRepository $vehicleRepo, array $filters): array
    {
        $labels = [];

        // labels marques sélectionnées
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

        // labels statuts
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
