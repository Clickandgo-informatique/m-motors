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

    // retourne l'utilisateur connecté typé
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

    // création dossier depuis véhicule côté user
    #[Route('/dossier/create/{id<\d+>}/{type}', name: 'dossier_create', methods: ['GET', 'POST'])]
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

        $customer = $user->getCustomer();

        if (!$customer) {
            $this->addFlash('error', 'aucun client associé');
            return $this->redirectToRoute('app_profile');
        }

        if ($vehicle->isLocked()) {
            $this->addFlash('error', 'véhicule indisponible');
            return $this->redirectToRoute('vehicles_index');
        }

        try {
            $dossier = $service->createFromVehicle($customer, $vehicle, $dossierType);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'erreur création dossier');
            return $this->redirectToRoute('vehicles_index');
        }

        $this->addFlash('success', 'dossier créé');

        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    // création dossier admin depuis véhicule
    #[Route('/admin/dossier/create/{id<\d+>}/{type}', name: 'admin_dossier_create', methods: ['GET', 'POST'])]
    public function adminCreateFromVehicle(
        Vehicle $vehicle,
        string $type,
        Request $request,
        DossierCreationService $service
    ): Response {
        if (!$this->isAdminOrManager()) {
            throw $this->createAccessDeniedException();
        }

        $dossierType = DossierType::tryFrom($type);

        if (!$dossierType) {
            $this->addFlash(
                'error',
                'création administrateur impossible : type de dossier invalide'
            );

            return $this->redirectToRoute('vehicles_index');
        }

        $customerId = $request->request->get('customerId');

        if (!$customerId) {
            $this->addFlash(
                'error',
                'création administrateur impossible : aucun client sélectionné'
            );

            return $this->redirectToRoute('vehicles_index');
        }

        $customer = $this->em->getRepository(Customer::class)->find($customerId);

        if (!$customer) {
            $this->addFlash(
                'error',
                'création administrateur impossible : client introuvable'
            );

            return $this->redirectToRoute('vehicles_index');
        }

        if ($vehicle->isLocked()) {
            $this->addFlash(
                'error',
                'création administrateur impossible : véhicule indisponible'
            );

            return $this->redirectToRoute('vehicles_index');
        }

        try {
            $dossier = $service->createFromVehicle(
                $customer,
                $vehicle,
                $dossierType
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'error',
                'création administrateur impossible : une erreur est survenue lors de la création du dossier'
            );

            return $this->redirectToRoute('vehicles_index');
        }

        $this->addFlash(
            'success',
            'dossier créé avec succès en mode administrateur'
        );

        return $this->redirectToRoute('admin_dossier_show', [
            'id' => $dossier->getId()
        ]);
    }
    // liste dossiers user
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

    // show dossier user
    #[Route('/dossier/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('DOSSIER_VIEW', $dossier);

        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier
        ]);
    }

    // list admin dossiers
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

    // show admin dossier
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

    // new admin dossier
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

    // edit admin dossier
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
            'title' => 'Modifier un dossier'
        ]);
    }

    // ajax search dossiers
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
