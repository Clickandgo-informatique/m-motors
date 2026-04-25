<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleFormType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicles')]
class VehicleController extends AbstractController
{
    /**
     * Vue principale du catalogue
     */
    #[Route('', name: 'vehicles', methods: ['GET'])]
    public function index(
        Request $request,
        VehicleRepository $repo,
        PaginatorInterface $paginator,
        SessionInterface $session
    ): Response {
        $defaultView = $this->isGranted(['ROLE_ADMIN', 'ROLE_MANAGER']) ? 'table' : 'grid';
        $view = $request->query->get('view') ?? $session->get('vehicle_view', $defaultView);
        $session->set('vehicle_view', $view);

        $qb = $repo->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->addSelect('vm', 'b', 'm')
            ->orderBy('v.id', 'DESC');

        $vehicles = $paginator->paginate($qb->getQuery(), $request->query->getInt('page', 1), 20);

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
            'view' => $view,
            'title' => 'Catalogue des véhicules'
        ]);
    }

    /**
     * Endpoint JSON pour l'autocomplete
     */
    #[Route('/ajax/search', name: 'vehicles_ajax_search', methods: ['GET'])]
    public function ajaxSearch(Request $request, VehicleRepository $repo): JsonResponse
    {
        $q = $request->query->get('q', '');

        // Repo personnalisé pour chercher sur marque, modèle, immatriculation
        $vehicles = $repo->searchByTerm($q); // retourne un tableau de Vehicles

        // Transformer les résultats pour le JS
        $items = array_map(function (Vehicle $v) {
            return [
                'id' => $v->getId(),
                'label' => sprintf('%s - %s %s', $v->getRegistrationNumber(), $v->getVehicleModel()->getBrand()->getName(), $v->getVehicleModel()->getModel()->getName()),
                'url' => $this->generateUrl('vehicle_show', ['id' => $v->getId()])
            ];
        }, $vehicles);

        return $this->json(['items' => $items]);
    }

    // --- CRUD classiques ---
    #[Route('/new', name: 'vehicle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleFormType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vehicle);
            $em->flush();
            $this->addFlash('success', 'Véhicule créé.');
            return $this->redirectToRoute('vehicles');
        }

        return $this->render('vehicles/new.html.twig', ['form' => $form, 'title' => 'Créer un véhicule']);
    }

    #[Route('/{id<\d+>}/edit', name: 'vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VehicleFormType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Véhicule modifié.');
            return $this->redirectToRoute('vehicles');
        }

        return $this->render('vehicles/_vehicle_form.html.twig', [
            'form' => $form,
            'vehicle' => $vehicle,
            'title' => 'Modifier le véhicule'
        ]);
    }

    #[Route('/{id<\d+>}', name: 'vehicle_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vehicle->getId(), $request->request->get('_token'))) {
            $em->remove($vehicle);
            $em->flush();
            $this->addFlash('success', 'Véhicule supprimé.');
        }
        return $this->redirectToRoute('vehicles');
    }
}
