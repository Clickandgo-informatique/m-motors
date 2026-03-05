<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry;


class VehicleController extends AbstractController
{
    #[Route('/vehicles/search', name: 'vehicle_search', methods: ['GET'])]
    public function search(
        Request $request,
        VehicleRepository $vehicleRepository
    ): JsonResponse {

        $term = $request->query->get('q', '');
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;

        if (trim($term) === '') {
            return new JsonResponse([
                'results' => [],
                'total' => 0,
            ]);
        }

        $vehicles = $vehicleRepository->searchPaginated($term, $limit, $offset);
        $total = $vehicleRepository->countSearch($term);

        return new JsonResponse([
            'results' => array_map(function ($vehicle) {
                return [
                    'id' => $vehicle->getId(),
                    'brand' => $vehicle->getVehicleModel()?->getBrand()?->getName(),
                    'model' => $vehicle->getVehicleModel()?->getModel()?->getName(),
                    'status' => $vehicle->getStatus(),
                    'registrationNumber' => $vehicle->getRegistrationNumber(),
                ];
            }, $vehicles),
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit),
        ]);
    }

    #[Route('/vehicles', name: 'vehicles')]
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

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/vehicles/{id}/edit', name: 'vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(VehicleType::class, $vehicle);
        $title = "Modifier un modèle de véhicule";

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('message', 'Les changements ont été enregistrés avec succès.');

            if ($request->isXmlHttpRequest()) {
                return new Response("OK");
            }

            return $this->redirectToRoute('vehicles');
        }

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('vehicles/_vehicle_form.html.twig', [
                'form' => $form->createView(),
                'vehicle' => $vehicle,
                'title' => $title,
            ]);

            return new JsonResponse(['html' => $html]);
        }

        return $this->render('vehicles/_vehicle_form.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
            'title' => $title,
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
