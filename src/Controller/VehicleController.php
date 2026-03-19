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

#[Route('/vehicles')]
class VehicleController extends AbstractController
{
    /**
     * Liste des véhicules
     */
    #[Route('', name: 'vehicles', methods: ['GET'])]
    public function index(
        Request $request,
        VehicleRepository $repo,
        PaginatorInterface $paginator
    ): Response {

        $query = $repo->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.variant', 'va')
            ->addSelect('vm', 'b', 'm', 'va')
            ->orderBy('v.id', 'DESC')
            ->getQuery();

        $vehicles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
            'title' => 'Catalogue des véhicules'
        ]);
    }

    /**
     * Création d'un véhicule
     */
    #[Route('/new', name: 'vehicle_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $vehicle = new Vehicle();

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule créé.');

            return $this->redirectToRoute('vehicles');
        }

        return $this->render('vehicles/new.html.twig', [
            'form' => $form,
            'title' => 'Créer un véhicule'
        ]);
    }

    /**
     * Edition véhicule
     */
    #[Route('/{id<\d+>}/edit', name: 'vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        $form = $this->createForm(VehicleType::class, $vehicle);
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

    /**
     * Recherche AJAX pour autocomplete
     */
    #[Route('/search', name: 'vehicle_search', methods: ['GET'])]
    public function search(
        Request $request,
        VehicleRepository $repo
    ): JsonResponse {

        $term = $request->query->get('q', '');

        if (!$term) {
            return $this->json(['results' => []]);
        }

        $items = $repo->searchPaginated($term, 10, 0);

        $results = [];

        foreach ($items as $item) {

            if (is_array($item)) {

                $results[] = [
                    'id' => $item['id'] ?? null,
                    'label' => $item['label']
                        ?? ($item['brand'] ?? '') . ' ' . ($item['model'] ?? '')
                ];
            } else {

                $vm = $item->getVehicleModel();

                $brand = $vm?->getBrand()?->getName() ?? '';
                $model = $vm?->getModel()?->getName() ?? '';
                $variant = $vm?->getVariant()?->getName() ?? '';

                $results[] = [
                    'id' => $item->getId(),
                    'label' => trim("$brand $model $variant")
                ];
            }
        }

        return $this->json([
            'results' => $results
        ]);
    }

    /**
     * Suppression
     */
    #[Route('/{id<\d+>}', name: 'vehicle_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        if ($this->isCsrfTokenValid('delete' . $vehicle->getId(), $request->request->get('_token'))) {

            $em->remove($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule supprimé.');
        }

        return $this->redirectToRoute('vehicles');
    }
}
