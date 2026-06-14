<?php

namespace App\Controller;

use App\Entity\VehicleModel;
use App\Form\VehicleModelFormType;
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
    /**
     * Page principale (HTML)
     * Source unique de vérité pour la liste + pagination KNP
     */
    #[Route('/vehicles/models', name: 'vehicles_models', methods: ['GET'])]
    public function index(
        Request $request,
        VehicleModelRepository $repo,
        PaginatorInterface $paginator
    ): Response {

        $term = trim($request->query->get('q', ''));
        $page = $request->query->getInt('page', 1);

        // Construction de la requête (filtrée ou non)
        $queryBuilder = $term
            ? $repo->searchQueryBuilder($term)
            : $repo->findAllWithRelations();

        // Pagination unique KNP
        $vehicleModels = $paginator->paginate(
            $queryBuilder,
            $page,
            20
        );

        return $this->render('vehicles_models/vehicles_models.html.twig', [
            'vm' => $vehicleModels,
            'search' => $term,
        ]);
    }

    /**
     * Endpoint AJAX pour FetchForm
     * Retourne HTML + pagination synchronisée
     */
    #[Route('/vehicles/models/ajax', name: 'vehicles_models_ajax', methods: ['GET'])]
    public function indexAjax(
        Request $request,
        VehicleModelRepository $repo,
        PaginatorInterface $paginator
    ): JsonResponse {

        $term = trim($request->query->get('q', ''));
        $page = $request->query->getInt('page', 1);

        $queryBuilder = $term
            ? $repo->searchQueryBuilder($term)
            : $repo->findAllWithRelations();

        $vehicleModels = $paginator->paginate(
            $queryBuilder,
            $page,
            20
        );

        return $this->json([
            // HTML du tableau (source unique via partial)
            'results' => $this->renderView('vehicles_models/_table.html.twig', [
                'vm' => $vehicleModels
            ]),

            // Pagination haute
            'paginationTop' => $this->renderView('vehicles_models/_pagination_info.html.twig', [
                'vm' => $vehicleModels
            ]),

            // Pagination basse
            'paginationBottom' => $this->renderView('vehicles_models/_pagination_info.html.twig', [
                'vm' => $vehicleModels
            ]),
        ]);
    }

    /**
     * Autocomplete uniquement (sans pagination KNP)
     * Doit rester simple et indépendant du système principal
     */
    #[Route('/vehicle-model/search', name: 'vehicle_model_search', methods: ['GET'])]
    public function search(Request $request, VehicleModelRepository $repo): JsonResponse
    {
        $term = trim($request->query->get('q', ''));

        if ($term === '') {
            return $this->json([
                'items' => []
            ]);
        }

        // réutilise ta méthode existante
        $models = $repo->searchPaginated($term, 10, 0);

        $items = array_map(function ($model) {
            $brand = $model['brand_name'] ?? '';
            $name = $model['model_name'] ?? '';
            $variant = $model['variant_name'] ?? '';

            return [
                'id' => $model['id'],
                'label' => trim($brand . ' ' . $name . ' ' . $variant),
                'url' => $this->generateUrl('vehicle_model_edit', [
                    'id' => $model['id']
                ])
            ];
        }, $models);

        return $this->json([
            'items' => $items
        ]);
    }

    /**
     * Création véhicule modèle
     */
    #[Route('/vehicles/models/new', name: 'vehicle_model_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $vm = new VehicleModel();

        $form = $this->createForm(VehicleModelFormType::class, $vm);

        $title = "Ajouter un nouveau modèle de véhicule";

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($vm);
            $em->flush();

            $this->addFlash('message', 'Modèle créé avec succès.');

            if ($request->isXmlHttpRequest()) {
                return new Response('OK');
            }

            return $this->redirectToRoute('vehicles_models');
        }

        return $this->render('vehicles/_vehicle_model_form.html.twig', [
            'form' => $form->createView(),
            'vm' => $vm,
            'title' => $title,
            'mode' => 'new'
        ]);
    }

    /**
     * Edition véhicule modèle
     */
    #[Route('/vehicles/models/{id}/edit', name: 'vehicle_model_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        VehicleModel $vm,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(VehicleModelFormType::class, $vm);

        $title = "Modifier un modèle de véhicule";

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            $this->addFlash('message', 'Modèle modifié avec succès.');

            if ($request->isXmlHttpRequest()) {
                return new Response('OK');
            }

            return $this->redirectToRoute('vehicles_models');
        }

        return $this->render('vehicles/_vehicle_model_form.html.twig', [
            'form' => $form->createView(),
            'vm' => $vm,
            'title' => $title,
            'mode' => 'edit'
        ]);
    }

    /**
     * Suppression
     */
    #[Route('/vehicles/models/{id}', name: 'vehicle_model_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        VehicleModel $vm,
        EntityManagerInterface $em
    ): Response {

        if ($this->isCsrfTokenValid(
            'delete_vehicle_model_' . $vm->getId(),
            $request->request->get('_token')
        )) {

            $em->remove($vm);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response('OK');
            }

            $this->addFlash('message', 'Modèle supprimé avec succès.');
        }

        return $this->redirectToRoute('vehicles_models');
    }
}
