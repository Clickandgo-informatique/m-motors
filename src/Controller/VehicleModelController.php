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
    #[Route('/vehicle-model/search', name: 'vehicle_model_search', methods: ['GET'])]
    public function search(Request $request, VehicleModelRepository $repo): JsonResponse
    {
        // récupération du champ envoyé par le formulaire Symfony
        $vehicle = $request->query->all('vehicle');

        // texte recherché
        $term = trim($vehicle['vehicleModelSearch'] ?? '');

        // pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // si recherche vide on renvoie une structure vide
        if ($term === '') {
            return $this->json([
                'results' => [],
                'total' => 0,
                'page' => 1,
                'pages' => 0,
            ]);
        }

        // appel repository
        $models = $repo->searchPaginated($term, $limit, $offset);

        // nombre total pour pagination
        $total = $repo->countSearch($term);

        return $this->json([
            'results' => array_map(function ($model) {

                // ⚠️ clés venant du SELECT du repository
                $brand = $model['brand_name'] ?? '';
                $name  = $model['model_name'] ?? '';
                $variant = $model['variant_name'] ?? '';

                // label affiché dans le dropdown
                $label = trim($brand . ' ' . $name . ' ' . $variant);

                return [
                    'id' => $model['id'],
                    'brand' => $brand,
                    'model' => $name,
                    'variant' => $variant,
                    'label' => $label,
                ];
            }, $models),

            'total' => $total,
            'page' => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/vehicles/models', name: 'vehicles_models', methods: ['GET'])]
    public function index(
        Request $request,
        VehicleModelRepository $repo,
        PaginatorInterface $paginator
    ): Response {

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

        return $this->render('vehicles/vehicles_models.html.twig', [
            'vm' => $vehicleModels,
            'search' => $term,
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
            $html = $this->renderView('vehicles/_vehicle_model_form.html.twig', [
                'form' => $form->createView(),
                'vm' => $vm,
                'title' => $title,
            ]);

            return new JsonResponse(['html' => $html]);
        }

        return $this->render('vehicles/_vehicle_model_form.html.twig', [
            'form' => $form->createView(),
            'vm' => $vm,
            'title' => $title,
        ]);
    }

    #[Route('/vehicles/models/{id}/edit', name: 'vehicle_model_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        VehicleModel $vm,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(VehicleModelType::class, $vm);
        $title = "Modifier un modèle de véhicule";

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    public function delete(
        Request $request,
        VehicleModel $vm,
        EntityManagerInterface $em
    ): Response {

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
