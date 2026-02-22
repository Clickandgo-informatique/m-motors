<?php

namespace App\Controller;

use App\Repository\VehicleModelRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{
    #[Route('/vehicles/models', name: 'vehicles_models')]
    public function index(Request $request, VehicleModelRepository $repo, PaginatorInterface $paginator): Response
    {
        $query = $repo->findAllWithRelations();
        
        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('vehicles/vehicles_models.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }
}
