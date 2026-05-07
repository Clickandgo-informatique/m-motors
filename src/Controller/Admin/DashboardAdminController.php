<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Repository\VehicleRepository;
use App\Service\Dashboard\VehicleDashboardService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/dashboard')]
class DashboardAdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(
        Request $request,
        VehicleDashboardService $dashboardService,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {

        /*
         * =========================================================
         * KPI + DATA DASHBOARD (SERVICE)
         * =========================================================
         */

        $stats = $dashboardService->getStats();
        $stock = $dashboardService->getStockByModel();
        $usage = $dashboardService->getUsageDistribution();

        /*
         * =========================================================
         * MARQUES (UI ONLY)
         * =========================================================
         */

        $brandQuery = $em->createQueryBuilder()
            ->select('b')
            ->from(Brand::class, 'b')
            ->orderBy('b.name', 'ASC');

        $brands = $paginator->paginate(
            $brandQuery,
            $request->query->getInt('brandsPage', 1),
            5,
            ['pageParameterName' => 'brandsPage']
        );

        /*
         * =========================================================
         * MARQUE ACTIVE (OPTIONNEL UI)
         * =========================================================
         */

        $selectedBrandId = $request->query->get('brandId');

        /*
         * =========================================================
         * VIEW
         * =========================================================
         */

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'stock' => $stock,
            'usage' => $usage,
            'brands' => $brands,
            'selectedBrandId' => $selectedBrandId,
        ]);
    }
    #[Route('/admin/dashboard/search', name: 'dashboard_vehicle_search', methods: ['GET'])]
    public function dashboardSearch(
        Request $request,
        VehicleRepository $repo
    ): Response {
        $q = $request->query->get('q');

        $items = $repo->searchForAutocomplete([], $q, 10);

        return $this->json($items);
    }

    #[Route('/admin/dashboard/results', name: 'admin_dashboard_results', methods: ['GET'])]
    public function results(
        Request $request,
        VehicleRepository $vehicleRepository
    ): Response {

        $vehicleId = $request->query->get('vehicleId');
        $search = $request->query->get('q');

        $qb = $vehicleRepository->getFilteredQueryBuilder([]);

        /*
     * CAS 1 : sélection depuis autocomplete (prioritaire)
     */
        if (!empty($vehicleId)) {

            $qb->andWhere('v.id = :id')
                ->setParameter('id', (int) $vehicleId);
        }

        /*
     * CAS 2 : recherche texte classique
     */ elseif (!empty($search)) {

            $search = '%' . mb_strtolower(trim($search)) . '%';

            $qb->andWhere(
                'LOWER(v.registrationNumber) LIKE :search
            OR LOWER(v.vin) LIKE :search
            OR LOWER(m.name) LIKE :search
            OR LOWER(b.name) LIKE :search'
            )->setParameter('search', $search);
        }

        $vehicles = $qb
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard/_vehicles_search_results.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }
}
