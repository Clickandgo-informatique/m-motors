<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Financing;
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
use Symfony\Component\Routing\Attribute\Route;

class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    // retourne l'utilisateur connecté typé user
    private function getAppUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    // vérifie si admin ou manager
    private function isAdminOrManager(): bool
    {
        return $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER');
    }

    // ouverture de la modale de création de dossier depuis un véhicule
    #[Route('/dossier/create/modal/{id<\d+>}/{type}', name: 'dossier_create_modal', methods: ['GET'])]
    public function createModal(
        Vehicle $vehicle,
        string $type
    ): Response {
        $this->getAppUser();

        $dossierType = DossierType::tryFrom($type);

        if (!$dossierType) {
            throw $this->createNotFoundException('type de dossier invalide');
        }

        return $this->render('dossier/_create_modal.html.twig', [
            'vehicle' => $vehicle,
            'type' => $type
        ]);
    }

    // création dossier depuis véhicule (user et admin)
    #[Route('/dossier/create/{id<\d+>}/{type}', name: 'dossier_create', methods: ['POST'])]
    public function createFromVehicle(
        Vehicle $vehicle,
        string $type,
        Request $request,
        DossierCreationService $service
    ): Response {

        $user = $this->getAppUser();

        $dossierType = DossierType::tryFrom($type);

        if (!$dossierType) {
            $this->addFlash('error', 'type de dossier invalide');
            return $this->redirectToRoute('vehicles_index');
        }

        // cas admin ou manager : client fourni par le formulaire
        if ($this->isAdminOrManager()) {

            $customerId = $request->request->get('customerId');

            if (!$customerId) {
                $this->addFlash('error', 'aucun client sélectionné');
                return $this->redirectToRoute('vehicles_index');
            }

            $customer = $this->em->getRepository(Customer::class)->find($customerId);

            if (!$customer) {
                $this->addFlash('error', 'client introuvable');
                return $this->redirectToRoute('vehicles_index');
            }
        } else {
            // cas user : client lié au compte
            $customer = $user->getCustomer();

            if (!$customer) {
                $this->addFlash('error', 'aucun client associé');
                return $this->redirectToRoute('app_profile');
            }
        }

        try {
            $result = $service->createFromVehicle($customer, $vehicle, $dossierType);

            $dossier = $result['dossier'];

            if ($result['created']) {
                $this->addFlash('success', 'Le dossier a été créé avec succès.');
            } else {
                $this->addFlash('info', 'Un dossier existe déjà pour ce véhicule.');
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', 'erreur création dossier');
            return $this->redirectToRoute('vehicles_index');
        }

        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    // liste des dossiers utilisateur connecté
    #[Route('/dossier/my/list', name: 'dossier_user_list', methods: ['GET'])]
    public function myDossiers(DossierRepository $repository): Response
    {
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

    // affichage d'un dossier utilisateur
    #[Route('/dossier/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('DOSSIER_VIEW', $dossier);

        $logs = $dossier->getWorkflowLogs()->toArray();

        usort($logs, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());

        $lastLog = end($logs);

        $currentStatus = $lastLog ? $lastLog->getToStatus() : 'draft';

        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier,
            'currentStatus' => $currentStatus,
            'logs' => $logs
        ]);
    }

    // liste admin des dossiers avec pagination
    #[Route('/admin/dossier/list', name: 'admin_dossier_list', methods: ['GET'])]
    public function adminList(
        DossierRepository $repository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {

        if (!$this->isAdminOrManager()) {
            throw $this->createAccessDeniedException();
        }

        $query = $repository->createQueryBuilder('d')
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

    // affichage dossier admin avec logs workflow
    #[Route('/admin/dossier/{id<\d+>}', name: 'admin_dossier_show', methods: ['GET'])]
    public function adminShow(
        Dossier $dossier,
        DossierWorkflowLogRepository $repo
    ): Response {

        if (!$this->isAdminOrManager()) {
            throw $this->createAccessDeniedException();
        }

        $workflowLogs = $repo->findByDossier($dossier->getId());

        return $this->render('admin/dossier/show.html.twig', [
            'dossier' => $dossier,
            'workflowLogs' => $workflowLogs
        ]);
    }

    // création dossier admin manuel
    #[Route('/admin/dossier/new', name: 'admin_dossier_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if (!$this->isAdminOrManager()) {
            throw $this->createAccessDeniedException();
        }

        $dossier = new Dossier();

        if ($dossier->getFinancing() === null) {
            $financing = new Financing();
            $financing->setDossier($dossier);
            $dossier->setFinancing($financing);
        }

        $form = $this->createForm(DossierFormType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($dossier);
            $this->em->flush();

            $this->addFlash('success', 'dossier créé');

            return $this->redirectToRoute('admin_dossier_list');
        }

        return $this->render('admin/dossier/edit.html.twig', [
            'form' => $form->createView(),
            'title' => 'créer un dossier'
        ]);
    }

    // edition dossier admin
    #[Route('/admin/dossier/{id<\d+>}/edit', name: 'admin_dossier_edit', methods: ['GET', 'POST'])]
    public function edit(
        DossierRepository $repo,
        int $id,
        Request $request
    ): Response {

        if (!$this->isAdminOrManager()) {
            throw $this->createAccessDeniedException();
        }

        $dossier = $repo->find($id);

        if (!$dossier) {
            throw $this->createNotFoundException('dossier introuvable');
        }

        if ($dossier->getFinancing() === null) {
            $financing = new Financing();
            $financing->setDossier($dossier);
            $dossier->setFinancing($financing);
        }

        $form = $this->createForm(DossierFormType::class, $dossier, [
            'is_admin' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'dossier mis à jour');

            return $this->redirectToRoute('admin_dossier_show', [
                'id' => $dossier->getId()
            ]);
        }

        return $this->render('admin/dossier/edit.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
            'title' => 'modifier un dossier'
        ]);
    }

    // recherche ajax dossiers + autocomplete
    #[Route('/dossiers/ajax-search', name: 'dossiers_ajax_search', methods: ['GET', 'POST'])]
    public function search(
        Request $request,
        DossierRepository $repo,
        PaginatorInterface $paginator
    ): JsonResponse {

        $data = json_decode($request->getContent(), true) ?: $request->query->all();

        $searchTerm = trim((string) ($data['q'] ?? ''));
        $page = (int) ($data['page'] ?? 1);

        $isAutocomplete = $request->query->getBoolean('autocomplete');

        if ($isAutocomplete) {

            if (mb_strlen($searchTerm) < 2) {
                return $this->json(['items' => []]);
            }

            $results = $repo->findForAutocomplete($searchTerm);

            $items = [];

            foreach ($results as $dossier) {
                $items[] = [
                    'id' => (int) $dossier['id'],
                    'label' => $dossier['dossierCode'] ?? '',
                    'url' => $this->generateUrl('admin_dossier_edit', [
                        'id' => (int) $dossier['id']
                    ])
                ];
            }

            return $this->json(['items' => $items]);
        }

        $query = $repo->searchForPaginator($searchTerm);

        $dossiers = $paginator->paginate($query, $page, 20);

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
            'currentPage' => $dossiers->getCurrentPageNumber()
        ]);
    }
}
