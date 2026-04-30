<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Entity\VehicleModel;
use App\Enum\VehicleStatus;
use App\Repository\VehicleRepository;
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
        VehicleRepository $vehicleRepository,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {

        /*
         * =========================================================
         * KPI
         * =========================================================
         */
        $stats = $vehicleRepository->createQueryBuilder('v')
            ->select('COUNT(v.id) as total')
            ->addSelect("SUM(CASE WHEN v.status = :available THEN 1 ELSE 0 END) as available")
            ->addSelect("SUM(CASE WHEN v.status = :rented THEN 1 ELSE 0 END) as rented")
            ->addSelect("SUM(CASE WHEN v.status = :reserved THEN 1 ELSE 0 END) as reserved")
            ->addSelect("SUM(CASE WHEN v.status = :sold THEN 1 ELSE 0 END) as sold")
            ->setParameter('available', VehicleStatus::AVAILABLE->value)
            ->setParameter('rented', VehicleStatus::RENTED->value)
            ->setParameter('reserved', VehicleStatus::RESERVED->value)
            ->setParameter('sold', VehicleStatus::SOLD->value)
            ->getQuery()
            ->getSingleResult();

        /*
         * =========================================================
         * PAGINATION MARQUES
         * =========================================================
         */
        $brandQuery = $em->createQueryBuilder()
            ->select('b')
            ->from(Brand::class, 'b')
            ->orderBy('b.name', 'ASC');

        $brands = $paginator->paginate(
            $brandQuery,
            $request->query->getInt('brandPage', 1),
            5
        );

        /*
         * =========================================================
         * MARQUE ACTIVE
         * =========================================================
         */
        $brandId = $request->query->get('brandId');

        $models = null;

        if ($brandId) {

            $modelQuery = $em->createQueryBuilder()
                ->select('vm')
                ->from(VehicleModel::class, 'vm')
                ->where('vm.brand = :brand')
                ->setParameter('brand', $brandId)
                ->orderBy('vm.id', 'DESC');

            $models = $paginator->paginate(
                $modelQuery,
                $request->query->getInt('modelPage', 1),
                10
            );
        }

        /*
         * =========================================================
         * STOCK MAP
         * =========================================================
         */
        $rows = $vehicleRepository->createQueryBuilder('v')
            ->select('IDENTITY(v.vehicleModel) as modelId')
            ->addSelect('COUNT(v.id) as total')
            ->addSelect("SUM(CASE WHEN v.status = :available THEN 1 ELSE 0 END) as available")
            ->addSelect("SUM(CASE WHEN v.status = :rented THEN 1 ELSE 0 END) as rented")
            ->groupBy('v.vehicleModel')
            ->setParameter('available', VehicleStatus::AVAILABLE->value)
            ->setParameter('rented', VehicleStatus::RENTED->value)
            ->getQuery()
            ->getArrayResult();

        $stockMap = [];
        foreach ($rows as $r) {
            $stockMap[$r['modelId']] = $r;
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'brands' => $brands,
            'models' => $models,
            'selectedBrandId' => $brandId,
            'stockMap' => $stockMap,
        ]);
    }
}
