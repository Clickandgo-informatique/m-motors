<?php

namespace App\Controller;

use App\Entity\VehicleModel;
use App\Form\VehicleModelType;
use App\Repository\VehicleModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VehicleController extends AbstractController
{
    #[Route('/vehicles/models', name: 'vehicles_models', methods: ['GET'])]
    public function index(Request $request, VehicleModelRepository $repo, PaginatorInterface $paginator): Response
    {
        $query = $repo->findAllWithRelations();

        $vm = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('vehicles/vehicles_models.html.twig', [
            'vm' => $vm,
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
