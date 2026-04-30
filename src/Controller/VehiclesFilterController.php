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

#[Route('/vehicles')]
class VehiclesFilterController extends AbstractController
{
    #[Route('', name: 'vehicles', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepo,
        Request $request,
        PaginatorInterface $paginator,
        SessionInterface $session
    ): Response {

        $filters = $request->query->all('filters') ?? [];
        $searchTerm = $request->query->get('q');

        $view = $filters['view']
            ?? $session->get('vehicle_view', 'grid');

        $session->set('vehicle_view', $view);

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
            'brands' => $vehicleRepo->getUsedBrands(),
            'bodyTypes' => $vehicleRepo->getUsedBodyTypes(),
            'fuelTypes' => $vehicleRepo->getUsedFuelTypes(),
            'registrationYears' => $vehicleRepo->getRegistrationYears()['years'],
            'registrationYearsMin' => $vehicleRepo->getRegistrationYears()['min'],
            'registrationYearsMax' => $vehicleRepo->getRegistrationYears()['max'],
            'view' => $view
        ]);
    }

    #[Route('/ajax/search', name: 'vehicles_ajax_search', methods: ['GET', 'POST'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator,
        SessionInterface $session
    ): JsonResponse {

        $data = $request->query->all();

        $mode = $data['mode'] ?? 'search';

        if ($mode === 'autocomplete') {
            $items = $vehicleRepo->searchForAutocomplete(
                [],
                $data['q'] ?? null,
                10
            );

            return $this->json([
                'items' => $items
            ]);
        }

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;
        $page = $data['page'] ?? 1;

        $view = $filters['view']
            ?? $session->get('vehicle_view', 'grid');

        $session->set('vehicle_view', $view);

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate($query, $page, 20);

        $resultsHtml = $this->renderView(
            $view === 'table'
                ? 'vehicles/_vehicles_table_body.html.twig'
                : 'vehicles/_vehicles_gallery_items.html.twig',
            [
                'vehicles' => $vehicles
            ]
        );

        $paginationHtml = $this->renderView(
            'vehicles/_pagination_info.html.twig',
            [
                'vehicles' => $vehicles
            ]
        );

        return $this->json([
            'results' => $resultsHtml,
            'paginationTop' => $paginationHtml,
            'paginationBottom' => $paginationHtml,
        ]);
    }

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
}
