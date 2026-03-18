<?php

namespace App\Controller;

use App\Repository\BodyTypeRepository;
use App\Repository\BrandRepository;
use App\Repository\FuelTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehiclesFilterController extends AbstractController
{
    //Afficher les différents filtres disponibles concernant les véhicules

    #[Route(path: '/vehicles/filter/brands', name: 'vehicles_filter_brands', methods: ['GET'])]
    public function getFilters(
        BrandRepository $brandRepo,
        BodyTypeRepository $bodyTypeRepo,
        FuelTypeRepository $fuelTypeRepo
    ): Response {
        $brands = $brandRepo->getBrands();
        $bodyTypes = $bodyTypeRepo->getBodyTypes();
        $fuelTypes = $fuelTypeRepo->getFuelTypes();

        return $this->render('vehicles/_vehicles_filters.html.twig', [
            'brands' => $brands,
            'bodyTypes' => $bodyTypes,
            'fuelTypes' => $fuelTypes
        ]);
    }
}
