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
        $featuredVehicles=$repo->getFeaturedVehicles();
        return $this->render('home/index.html.twig',[
            'vehicles'=>$featuredVehicles
        ]);
    }
}
