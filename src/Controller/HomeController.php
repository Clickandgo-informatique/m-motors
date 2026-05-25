<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_home')]
    public function index(VehicleRepository $repo): Response
    {
        //Recherche les véhicules mis en avant pour la galerie homepage
        $featuredVehicles=$repo->findBy(['isFeatured'=>'true']);
        
        return $this->render('home/index.html.twig',[
            'vehicles'=>$featuredVehicles
        ]);
    }
}
