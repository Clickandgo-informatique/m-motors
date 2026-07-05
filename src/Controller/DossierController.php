<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Financing;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\DossierStatus;
use App\Form\DossierFormType;
use App\Repository\DossierRepository;
use App\Repository\DossierWorkflowLogRepository;
use App\Repository\EmailLogRepository;
use App\Service\Dossier\DossierCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry;

class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    private function getAppUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function isAdminOrManager(): bool
    {
        return $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER');
    }

    #[Route('/dossier/create/modal/{id<\d+>}', name: 'dossier_create_modal', methods: ['GET'])]
    public function createModal(Vehicle $vehicle): Response
    {
        $this->getAppUser();

        return $this->render('dossier/_create_modal.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/dossier/create/{id<\d+>}/', name: 'dossier_create', methods: ['POST'])]
    public function createFromVehicle(
        Vehicle $vehicle,
        Request $request,
        DossierCreationService $service
    ): Response {
        $user = $this->getAppUser();

        try {
            $dossierType = DossierType::from($request->request->get('type'));
        } catch (\ValueError) {
            $this->addFlash('error', 'type de dossier invalide');
            return $this->redirectToRoute('vehicles_index');
        }

        if ($this->isAdminOrManager()) {
            $customerId = $request->request->get('customer');

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
            $customer = $user->getCustomer();

            if (!$customer) {
                $this->addFlash('error', 'aucun client associé');
                return $this->redirectToRoute('app_profile');
            }
        }

        try {
            $result = $service->createFromVehicle($customer, $vehicle, $dossierType);

            $dossier = $result['dossier'] ?? null;

            if (!$dossier instanceof Dossier) {
                $this->addFlash('error', 'erreur création dossier');
                return $this->redirectToRoute('vehicles_index');
            }

            $this->addFlash(
                $result['created'] ? 'success' : 'info',
                $result['created']
                    ? 'Le dossier a été créé avec succès.'
                    : 'Un dossier existe déjà pour ce véhicule.'
            );
        } catch (\Throwable) {
            $this->addFlash('error', 'erreur création dossier');
            return $this->redirectToRoute('vehicles_index');
        }

        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    #[Route('/dossier/my/list', name: 'dossier_user_list', methods: ['GET'])]
    public function myDossiers(DossierRepository $repository): Response
    {
        $user = $this->getAppUser();

        $customer = $user->getCustomer();

        if (!$customer) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('dossier/index.html.twig', [
            'dossiers' => $repository->findBy(
                ['customer' => $customer],
                ['createdAt' => 'DESC']
            )
        ]);
    }

    #[Route('/dossier/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(
        DossierRepository $repo,
        int $id,
        Registry $workflowRegistry
    ): Response {
        $dossier = $repo->findWithLogs($id);

        $this->denyAccessUnlessGranted('DOSSIER_VIEW', $dossier);

        $workflow = $workflowRegistry->get($dossier);
        $marking = $workflow->getMarking($dossier);

        $currentStatus = array_key_first($marking->getPlaces()) ?? 'draft';

        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier,
            'currentStatus' => $currentStatus,
            'workflowLogs' => $dossier->getWorkflowLogs() ?? [],
            'statuses' => DossierStatus::cases(),
        ]);
    }

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
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery();

        $dossiers = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/dossier/list.html.twig', [
            'dossiers' => $dossiers
        ]);
    }

    #[Route('/admin/dossier/{id<\d+>}', name: 'admin_dossier_show', methods: ['GET'])]
    public function adminShow(
        Dossier $dossier,
        DossierWorkflowLogRepository $repo,
        EmailLogRepository $emailLogRepository
    ): Response {
        if (!$this->isAdminOrManager()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier,
            'workflowLogs' => $repo->findByDossier($dossier->getId()),
            'currentStatus' => null,
            'statuses' => DossierStatus::cases(),
            'emailLogs'=>$emailLogRepository->findLatestByDossier($dossier->getId(),10)
        ]);
    }

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
            'title' => 'Créer un dossier',
            'dossier' => $dossier
        ]);
    }

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

    #[Route('/dossiers/ajax-search', name: 'dossiers_ajax_search', methods: ['GET', 'POST'])]
    public function ajaxSearch(
        Request $request,
        DossierRepository $repo,
        PaginatorInterface $paginator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? $request->query->all();

        $search = trim((string) ($data['q'] ?? ''));
        $page = (int) ($data['page'] ?? 1);

        $qb = $search && mb_strlen($search) >= 2
            ? $repo->searchForPaginator($search)
            : $repo->createQueryBuilder('d')->orderBy('d.createdAt', 'DESC');

        $dossiers = $paginator->paginate($qb, $page, 20);

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
        ]);
    }
}
