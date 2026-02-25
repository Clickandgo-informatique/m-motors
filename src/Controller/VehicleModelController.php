<?php

namespace App\Controller;

use App\Entity\VehicleModel;
use App\Form\VehicleModelType;
use App\Repository\VehicleModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleModelController extends AbstractController
{
    #[Route('/vehicle-model/search', name: 'vehicle_model_search')]
    public function search(Request $request, VehicleModelRepository $repo): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Total réel
        $total = $repo->countSearch($q);

        // Page courante (tableaux, pas entités)
        $items = $repo->searchPaginated($q, $limit, $offset);

        return $this->json([
            'items' => $items,   // ✔ tableaux OK
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }



    #[Route('/vehicles/models', name: 'vehicles_models', methods: ['GET'])]
    public function index(Request $request, VehicleModelRepository $repo, PaginatorInterface $paginator): Response
    {
        $term = $request->query->get('q', '');

        if ($term) {
            // Recherche simple
            $results = $repo->search($term);

            // Pas besoin de pagination pour une recherche
            return $this->render('vehicles/vehicles_models.html.twig', [
                'vm' => $results,
                'search' => $term,
            ]);
        }

        // Sinon : liste complète paginée
        $query = $repo->findAllWithRelations();

        $vm = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('vehicles/vehicles_models.html.twig', [
            'vm' => $vm,
            'search' => '',
        ]);
    }

    #[Route('/vehicles/models/new', name: 'vehicle_model_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $vm = new VehicleModel();
        $form = $this->createForm(VehicleModelType::class, $vm);
        $title = "Ajouter un nouveau modèle de véhicule";

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vm);
            $em->flush();

            $this->addFlash('message', 'Le nouveau modèle de véhicule a été enregistré en base avec succès.');
        }

        return $this->render('vehicles/vehicle_model_form.html.twig', ['form' => $form->createView(), 'vm' => $vm, 'title' => $title]);
    }
    #[Route('/vehicles/models/{id}/edit', name: 'vehicle_model_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, EntityManagerInterface $em, VehicleModelRepository $repo): Response
    {
        $vm = $repo->find($id);
        $form = $this->createForm(VehicleModelType::class, $vm);

        $title = "Modifier un modèle de véhicule";

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vm);
            $em->flush();

            $this->addFlash('message', 'Les changements concernant le modèle de véhicule ont été enregistrés en base avec succès.');
        }

        return $this->render('vehicles/vehicle_model_form.html.twig', ['form' => $form->createView(), 'vm' => $vm, 'title' => $title]);
    }
}
