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
        // --- Mode d'affichage par défaut selon rôle ---
        $defaultView = $this->isGranted(['ROLE_ADMIN', 'ROLE_MANAGER']) ? 'table' : 'grid';
        $view = $request->query->get('view') ?? $session->get('vehicle_view', $defaultView);
        $session->set('vehicle_view', $view);

        // --- QueryBuilder initial ---
        $qb = $repo->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.variant', 'va')
            ->addSelect('vm', 'b', 'm', 'va')
            ->orderBy('v.id', 'DESC');

        // --- Pagination ---
        $vehicles = $paginator->paginate($qb->getQuery(), $request->query->getInt('page', 1), 20);

        return $this->render('vehicles/index.html.twig', [
            'vehicles' => $vehicles,
            'view' => $view,
            'title' => 'Catalogue des véhicules'
        ]);
    }

    /**
     * Filtres AJAX (table/grid + pagination + badges)
     */
    #[Route('/ajax/filters', name: 'vehicles_filters', methods: ['POST'])]
    public function filters(
        Request $request,
        VehicleRepository $repo,
        PaginatorInterface $paginator,
        SessionInterface $session
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $filters = $data['filters'] ?? [];
        $page = isset($data['page']) ? (int)$data['page'] : 1;

        // --- Lecture du mode view ---
        $view = $filters['view'] ?? $session->get('vehicle_view', 'grid');
        $session->set('vehicle_view', $view);

        // --- QueryBuilder de base ---
        $qb = $repo->createQueryBuilder('v')
            ->leftJoin('v.vehicleModel', 'vm')
            ->leftJoin('vm.brand', 'b')
            ->leftJoin('vm.model', 'm')
            ->leftJoin('vm.variant', 'va')
            ->addSelect('vm', 'b', 'm', 'va')
            ->orderBy('v.id', 'DESC');

        // --- Application des filtres dynamiques ---
        if (!empty($filters['brand'])) {
            $qb->andWhere('b.id IN (:brands)')
                ->setParameter('brands', $filters['brand']);
        }

        if (!empty($filters['bodyType'])) {
            $qb->andWhere('vm.bodyType IN (:bodyTypes)')
                ->setParameter('bodyTypes', $filters['bodyType']);
        }

        if (!empty($filters['fuelType'])) {
            $qb->andWhere('vm.fuelType IN (:fuelTypes)')
                ->setParameter('fuelTypes', $filters['fuelType']);
        }

        if (!empty($filters['yearMin'])) {
            $qb->andWhere('v.year >= :yearMin')
                ->setParameter('yearMin', $filters['yearMin']);
        }

        if (!empty($filters['yearMax'])) {
            $qb->andWhere('v.year <= :yearMax')
                ->setParameter('yearMax', $filters['yearMax']);
        }

        // --- Pagination ---
        $vehicles = $paginator->paginate($qb->getQuery(), $page, 20);

        // --- Rendu complet du container principal ---
        // Ce fragment contient :
        // 1) Résumé / badges
        // 2) Pagination (haut et bas)
        // 3) Affichage véhicules selon view
        $resultsHtml = $this->renderView($view === 'table'
            ? 'vehicles/_vehicles_table_body.html.twig'
            : 'vehicles/_vehicles_gallery_items.html.twig', [
            'vehicles' => $vehicles,
            'view' => $view,
            'filters' => $filters
        ]);

        return $this->json([
            'results' => $resultsHtml
        ]);
    }

    // --- CRUD classiques ---
    #[Route('/new', name: 'vehicle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle);
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
