<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Form\DossierFormType;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry;

#[Route('/dossier')]
class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Registry $workflowRegistry
    ) {}

    private function getAppUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    // =========================================================
    // FRONT
    // =========================================================

    #[Route('/create/{id}/{type}', name: 'dossier_create', methods: ['POST'])]
    public function createFromVehicle(Vehicle $vehicle, string $type): Response
    {
        $user = $this->getAppUser();
        $customer = $user->getCustomer();

        if (!$customer) {
            return $this->redirectToRoute('app_login');
        }

        $dossierType = DossierType::tryFrom($type);

        if (!$dossierType) {
            throw $this->createNotFoundException('Type de dossier invalide.');
        }

        $dossier = new Dossier();
        $dossier->setCustomer($customer)
            ->setVehicle($vehicle)
            ->setType($dossierType);

        $this->em->persist($dossier);
        $this->em->flush();

        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    #[Route('/my/list', name: 'dossier_my_list', methods: ['GET'])]
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

    #[Route('/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('DOSSIER_VIEW', $dossier);

        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    // =========================================================
    // ADMIN LIST (FIX IMPORTANT : KNP PAGINATOR)
    // =========================================================

    #[Route('/admin/list', name: 'admin_dossier_list', methods: ['GET'])]
    public function adminList(
        DossierRepository $repository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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

    #[Route('/admin/{id<\d+>}', name: 'admin_dossier_show', methods: ['GET'])]
    public function adminShow(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dossier/show.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    #[Route('/admin/new', name: 'admin_dossier_new', methods: ['GET', 'POST'])]
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

    // =========================================================
    // AJAX SEARCH (ARRAY + JSON UNIQUEMENT)
    // =========================================================

    #[Route('/ajax-search', name: 'dossiers_ajax_search', methods: ['GET', 'POST'])]
    public function search(
        Request $request,
        DossierRepository $dossierRepo,
        PaginatorInterface $paginator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: $request->query->all();

        $searchTerm = $data['q'] ?? '';
        $page = (int) ($data['page'] ?? 1);
        $isAutocomplete = ($data['autocomplete'] ?? false) === 'true';

        // =========================================================
        // AUTOCOMPLETE (ARRAY SIMPLE)
        // =========================================================
        if ($isAutocomplete) {
            $results = $dossierRepo->findForAutocomplete($searchTerm);

            $items = [];
            foreach ($results as $d) {
                $items[] = [
                    'id' => $d['id'],
                    'label' => $d['dossierCode'],
                    'url' => $this->generateUrl('dossier_show', ['id' => $d['id']])
                ];
            }

            return $this->json([
                'items' => $items
            ]);
        }

        // =========================================================
        // PAGINATION AJAX (KNP OK)
        // =========================================================
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
            'pagination' => $this->renderView('dossier/_pagination_info.html.twig', [
                'dossiers' => $dossiers
            ]),

            'totalItems' => $dossiers->getTotalItemCount(),
            'currentPage' => $dossiers->getCurrentPageNumber(),
        ]);
    }
}
