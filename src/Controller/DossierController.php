<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Form\DossierFormType;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/dossier')]
class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    // =========================================================
    // FRONT - CLIENT
    // =========================================================

    #[Route('/create/{id}/{type}', name: 'dossier_create', methods: ['POST'])]
    public function createFromVehicle(Vehicle $vehicle, string $type): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user || !$user->getCustomer()) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $user->getCustomer();

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
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user || !$user->getCustomer()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('dossier/index.html.twig', [
            'dossiers' => $repository->findBy(
                ['customer' => $user->getCustomer()],
                ['createdAt' => 'DESC']
            )
        ]);
    }

    #[Route('/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier, WorkflowInterface $workflow): Response
    {
        $this->denyAccessUnlessGranted('view', $dossier);

        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier,
            'workflow' => $workflow
        ]);
    }

    #[Route('/{id<\d+>}/transition/{transition}', name: 'dossier_transition', methods: ['POST'])]
    public function transition(
        Dossier $dossier,
        string $transition,
        WorkflowInterface $workflow
    ): Response {
        $this->denyAccessUnlessGranted('edit', $dossier);

        if (!$workflow->can($dossier, $transition)) {
            throw $this->createAccessDeniedException('Transition non autorisée');
        }

        $workflow->apply($dossier, $transition);
        $this->em->flush();

        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    // =========================================================
    // ADMIN
    // =========================================================

    #[Route('/admin/list', name: 'admin_dossier_list', methods: ['GET'])]
    public function adminList(DossierRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dossier/list.html.twig', [
            'dossiers' => $repository->findBy([], ['createdAt' => 'DESC'])
        ]);
    }

    #[Route('/admin/{id<\d+>}', name: 'admin_dossier_show', methods: ['GET'])]
    public function adminShow(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dossier/show.html.twig', [
            'dossier' => $dossier
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
}
