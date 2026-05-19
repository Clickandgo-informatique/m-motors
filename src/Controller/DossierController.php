<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Form\DossierFormType;
use App\Repository\DossierRepository;
use App\Repository\DossierWorkflowLogRepository;
use App\Service\Dossier\DossierCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * Retourne l'utilisateur connecté sous forme d'entité User.
     * Lance une exception si l'utilisateur n'est pas authentifié.
     */
    private function getAppUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    /**
     * Création d'un dossier à partir d'un véhicule et d'un type.
     */
    #[Route('/admin/dossier/create/{id}/{type}', name: 'dossier_create', methods: ['POST'])]
    public function createFromVehicle(
        Vehicle $vehicle,
        string $type,
        DossierCreationService $service
    ): Response {

        $user = $this->getAppUser();

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non connecté.');
            return $this->redirectToRoute('app_login');
        }

        $dossierType = DossierType::tryFrom($type);

        if (!$dossierType) {
            $this->addFlash('error', 'Type de dossier invalide.');
            return $this->redirectToRoute('vehicle_gallery');
        }

        $customer = $user->getCustomer();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER')) {

            // futur modal: si pas de customer sélectionné
            if ($customer === null) {
                $this->addFlash('error', 'Aucun client sélectionné.');
                return $this->redirectToRoute('vehicle_gallery');
            }
        }

        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {

            if ($customer === null) {
                $this->addFlash('error', 'Aucun client associé à votre compte utilisateur.');
                return $this->redirectToRoute('profile_edit');
            }
        }

        if ($vehicle->isLocked()) {
            $this->addFlash('error', 'Ce véhicule est indisponible.');
            return $this->redirectToRoute('vehicle_gallery');
        }

        try {
            $dossier = $service->createFromVehicle(
                $customer,
                $vehicle,
                $dossierType
            );
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création du dossier.');
            return $this->redirectToRoute('vehicle_gallery');
        }

        $this->addFlash('success', 'Dossier créé avec succès.');

        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    //Crée une modale de sélection d'options à l'ouverture d'un dossier
    #[Route('/admin/dossier/modal/create/{id}', name: 'dossier_modal_create', methods: ['GET'])]
    public function modalCreate(
        Vehicle $vehicle
    ): Response {

        $user = $this->getAppUser();

        if (!$user) {
            throw new \LogicException('Utilisateur non connecté.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedHttpException('Accès non autorisé.');
        }

        return $this->render('dossier/_modal_create_dossier.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }
    /**
     * Liste des dossiers du client connecté.
     */
    #[Route('/dossier/my/list', name: 'dossier_user_list', methods: ['GET'])]
    public function myDossiers(
        DossierRepository $repository
    ): Response {
        $user = $this->getAppUser();
        $customer = $user->getCustomer();

        if (!$customer) {
            throw $this->createAccessDeniedException();
        }

        $dossiers = $repository->findBy(
            ['customer' => $customer],
            ['createdAt' => 'DESC']
        );

        return $this->render('dossier/index.html.twig', [
            'dossiers' => $dossiers
        ]);
    }

    /**
     * Affichage d'un dossier (sécurisé par voter).
     */
    #[Route('/dossier/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('DOSSIER_VIEW', $dossier);


        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier
        ]);
    }

    /**
     * Liste admin des dossiers avec pagination.
     */
    #[Route(path: '/admin/dossier/list', name: 'admin_dossier_list', methods: ['GET'])]
    public function adminList(
        DossierRepository $repository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $query = $repository
            ->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC');

        $dossiers = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/dossier/list.html.twig', [
            'dossiers' => $dossiers
        ]);
    }

    /**
     * Affichage admin d'un dossier.
     */
    #[Route('/admin/dossier/{id<\d+>}', name: 'admin_dossier_show', methods: ['GET'])]
    public function adminShow(Dossier $dossier, DossierWorkflowLogRepository $dossierWorkflowLogRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $workflowLogs = $dossierWorkflowLogRepository->findByDossier($dossier->getId());

        return $this->render('admin/dossier/show.html.twig', [
            'dossier' => $dossier,
            'workflowLogs' => $workflowLogs,
        ]);
    }

    /**
     * Création d'un dossier côté admin via formulaire.
     */
    #[Route('/admin/dossier/new', name: 'admin_dossier_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $dossier = new Dossier();

        $form = $this->createForm(DossierFormType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($dossier);
            $this->em->flush();

            $this->addFlash('success', 'Dossier créé avec succès.');

            return $this->redirectToRoute('admin_dossier_list');
        }

        return $this->render('admin/dossier/new.html.twig', [
            'form' => $form->createView(),
            'title' => 'Créer un dossier'
        ]);
    }

    //Edition d'un dossier
    #[Route(path: '/admin/dossier/{id}/edit', name: 'admin_dossier_edit', methods: ['GET', 'POST'])]
    public function edit(DossierRepository $dossierRepo, int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $dossier = $dossierRepo->find($id);

        if (!$dossier) {
            throw $this->createNotFoundException('Dossier introuvable');
        }

        $form = $this->createForm(DossierFormType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($dossier);
            $this->em->flush();

            $this->addFlash('success', 'Dossier mis à jour avec succès.');

            return $this->redirectToRoute('admin_dossier_show', [
                'id' => $dossier->getId()
            ]);
        }

        return $this->render('admin/dossier/edit.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView()
        ]);
    }

    /**
     * Endpoint AJAX de recherche des dossiers.
     * Supporte l'autocomplete et la pagination.
     */
    #[Route('/dossiers/ajax-search', name: 'dossiers_ajax_search', methods: ['GET', 'POST'])]
    public function search(
        Request $request,
        DossierRepository $dossierRepo,
        PaginatorInterface $paginator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: $request->query->all();

        $searchTerm = trim((string) ($data['q'] ?? ''));
        $page = (int) ($data['page'] ?? 1);

        $isAutocomplete =
            filter_var($data['autocomplete'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || $request->query->getBoolean('autocomplete_param');

        if ($isAutocomplete) {
            $results = $dossierRepo->findForAutocomplete($searchTerm);

            $items = [];

            foreach ($results as $dossier) {
                $items[] = [
                    'id' => $dossier['id'],
                    'label' => $dossier['dossierCode']
                        ?? $dossier['dossier_code']
                        ?? '',
                    'url' => $this->generateUrl('dossier_show', [
                        'id' => $dossier['id']
                    ]),
                ];
            }

            return $this->json([
                'items' => $items
            ]);
        }

        $query = $dossierRepo->searchForPaginator($searchTerm);

        $dossiers = $paginator->paginate(
            $query,
            $page,
            20
        );

        return $this->json([
            'results' => $this->renderView('dossier/_dossiers_table.html.twig', [
                'dossiers' => $dossiers
            ]),

            'paginationTop' => $this->renderView('dossier/_pagination_info.html.twig', [
                'dossiers' => $dossiers
            ]),

            'paginationBottom' => $this->renderView('dossier/_pagination_info.html.twig', [
                'dossiers' => $dossiers
            ]),

            'totalItems' => $dossiers->getTotalItemCount(),
            'currentPage' => $dossiers->getCurrentPageNumber(),
        ]);
    }
}
