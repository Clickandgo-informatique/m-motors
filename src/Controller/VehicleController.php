<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry;

#[Route('/vehicle')]
class VehicleController extends AbstractController
{
    #[Route('/', name: 'vehicle_index')]
    public function index(
        VehicleRepository $vehicleRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $vehicleRepository->createQueryBuilder('v')
            ->orderBy('v.id', 'DESC')
            ->getQuery();

        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // nombre d'éléments par page
        );

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/{id}', name: 'vehicle_show')]
    public function show(Vehicle $vehicle): Response
    {
        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{id}/rent', name: 'vehicle_rent')]
    public function rent(
        Vehicle $vehicle,
        Registry $workflowRegistry,
        EntityManagerInterface $em
    ): Response {
        $workflow = $workflowRegistry->get($vehicle, 'vehicle_state_machine');

        if ($workflow->can($vehicle, 'vehicle_rent')) {
            $workflow->apply($vehicle, 'vehicle_rent');
            $em->flush();
            $this->addFlash('success', 'Véhicule marqué comme loué.');
        }

        return $this->redirectToRoute('vehicle_show', ['id' => $vehicle->getId()]);
    }

    #[Route('/{id}/return', name: 'vehicle_return')]
    public function returnVehicle(
        Vehicle $vehicle,
        Registry $workflowRegistry,
        EntityManagerInterface $em
    ): Response {
        $workflow = $workflowRegistry->get($vehicle, 'vehicle_state_machine');

        if ($workflow->can($vehicle, 'vehicle_return')) {
            $workflow->apply($vehicle, 'vehicle_return');
            $em->flush();
            $this->addFlash('success', 'Véhicule marqué comme disponible.');
        }

        return $this->redirectToRoute('vehicle_show', ['id' => $vehicle->getId()]);
    }

    #[Route('/{id}/maintenance', name: 'vehicle_maintenance')]
    public function maintenance(
        Vehicle $vehicle,
        Registry $workflowRegistry,
        EntityManagerInterface $em
    ): Response {
        $workflow = $workflowRegistry->get($vehicle, 'vehicle_state_machine');

        if ($workflow->can($vehicle, 'vehicle_maintenance')) {
            $workflow->apply($vehicle, 'vehicle_maintenance');
            $em->flush();
            $this->addFlash('success', 'Véhicule en maintenance.');
        }

        return $this->redirectToRoute('vehicle_show', ['id' => $vehicle->getId()]);
    }
}