<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicles')]
class VehiclesFilterController extends AbstractController
{
    /**
     * PAGE PRINCIPALE
     */
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

    /**
     * LISTE AJAX (GRID / TABLE + PAGINATION)
     */
    #[Route('/ajax/list', name: 'vehicles_ajax_list', methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        VehicleRepository $vehicleRepo,
        PaginatorInterface $paginator
    ): Response {

        $data = $request->query->all();

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;

        $page = max(1, (int) ($data['page'] ?? 1));
        $view = $filters['view'] ?? 'grid';

        $query = $vehicleRepo->getFilteredQueryBuilder($filters, $searchTerm);

        $vehicles = $paginator->paginate($query, $page, 10);

        return $this->render('vehicles/_vehicles_list.html.twig', [
            'vehicles' => $vehicles,
            'view' => $view
        ]);
    }

    /**
     * SIDEBAR FILTRES (DYNAMIQUE)
     */
    #[Route('/ajax/filters', name: 'vehicles_ajax_filters', methods: ['GET', 'POST'])]
    public function filters(
        Request $request,
        VehicleRepository $vehicleRepo
    ): Response {

        $data = $request->query->all();

        $filters = $data['filters'] ?? [];
        $searchTerm = $data['q'] ?? null;

        return $this->render('vehicles/_vehicles_filters.html.twig', [
            'brands' => $vehicleRepo->getUsedBrands($filters, $searchTerm),
            'bodyTypes' => $vehicleRepo->getUsedBodyTypes($filters, $searchTerm),
            'fuelTypes' => $vehicleRepo->getUsedFuelTypes($filters, $searchTerm),
            'registrationYears' => $vehicleRepo->getRegistrationYears()['years'],
            'registrationYearsMin' => $vehicleRepo->getRegistrationYears()['min'],
            'registrationYearsMax' => $vehicleRepo->getRegistrationYears()['max'],
        ]);
    }

    /**
     * AUTOCOMPLETE
     */
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
}
