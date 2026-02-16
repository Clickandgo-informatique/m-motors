<?php
// src/Controller/VehicleController.php
namespace App\Controller;

use App\Repository\BrandRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{
    #[Route('/vehicles', name: 'vehicles_index')]
    public function index(BrandRepository $brandRepository): Response
    {
        $brands = $brandRepository->findBy([], ['name' => 'ASC']);

        return $this->render('vehicles/index.html.twig', [
            'brands' => $brands,
        ]);
    }
}
