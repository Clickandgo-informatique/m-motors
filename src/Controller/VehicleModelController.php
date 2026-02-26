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

        $total = $repo->countSearch($q);
        $items = $repo->searchPaginated($q, $limit, $offset);

        return $this->json([
            'items' => $items,
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
            $results = $repo->search($term);

            return $this->render('vehicles/vehicles_models.html.twig', [
                'vm' => $results,
                'search' => $term,
            ]);
        }

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

            $this->addFlash('message', 'Le nouveau modèle de véhicule a été enregistré avec succès.');

            if ($request->isXmlHttpRequest()) {
                return new Response("OK");
            }

            return $this->redirectToRoute('vehicles_models');
        }

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('vehicles/vehicle_model_form.html.twig', [
                'form' => $form->createView(),
                'vm' => $vm,
                'title' => $title,
            ]);

            return new JsonResponse(['html' => $html]);
        }

        return $this->render('vehicles/_vehicle_model.html.twig', [
            'form' => $form->createView(),
            'vm' => $vm,
            'title' => $title,
        ]);
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

            $this->addFlash('message', 'Les changements ont été enregistrés avec succès.');

            if ($request->isXmlHttpRequest()) {
                return new Response("OK");
            }

            return $this->redirectToRoute('vehicles_models');
        }

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('vehicles/_vehicle_model_form.html.twig', [
                'form' => $form->createView(),
                'vm' => $vm,
                'title' => $title,
            ]);

            return new JsonResponse(['html' => $html]);
        }

        return $this->render('vehicles/vehicle_model.html.twig', [
            'form' => $form->createView(),
            'vm' => $vm,
            'title' => $title,
        ]);
    }

    #[Route('/vehicles/models/{id}/delete', name: 'vehicle_model_delete', methods: ['POST'])]
    public function delete(Request $request, VehicleModel $vm, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete_vehicle_model_' . $vm->getId(), $token)) {
            return new Response("INVALID_TOKEN", 400);
        }

        $em->remove($vm);
        $em->flush();

        $this->addFlash('message', 'Le modèle de véhicule a été supprimé avec succès.');

        if ($request->isXmlHttpRequest()) {
            return new Response("OK");
        }

        return $this->redirectToRoute('vehicles_models');
    }
}
