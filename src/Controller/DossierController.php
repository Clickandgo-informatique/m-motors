<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/dossier')]
class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    // ========================= FRONT CLIENT =========================

    /**
     * Création d'un dossier depuis un véhicule sélectionné
     * @param Vehicle $vehicle Le véhicule choisi
     * @param string $type "purchase" ou "rental"
     */
    #[Route('/create/{id}/{type}', name: 'dossier_create', methods: ['GET'])]
    public function createFromVehicle(Vehicle $vehicle, string $type): Response
    {
        /** @var \App\Entity\User $user */

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $user->getCustomer();
        if (!$customer) {
            throw $this->createAccessDeniedException('Aucun customer associé à l’utilisateur.');
        }

        if (!in_array($type, ['purchase', 'rental'])) {
            throw $this->createNotFoundException('Type de dossier invalide.');
        }

        $dossier = new Dossier();
        $dossier->setCustomer($customer)
            ->setVehicle($vehicle)
            ->setType($type);

        $this->em->persist($dossier);
        $this->em->flush();

        return $this->redirectToRoute('dossier_show', ['id' => $dossier->getId()]);
    }

    /**
     * Affiche un dossier client
     */
    #[Route('/{id}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('view', $dossier);
        return $this->render('dossier/show.html.twig', ['dossier' => $dossier]);
    }

    /**
     * Soumission d’un dossier
     */
    #[Route('/{id}/submit', name: 'dossier_submit', methods: ['POST'])]
    public function submit(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('edit', $dossier);

        if (!$dossier->isDraft()) {
            $this->addFlash('error', 'Dossier déjà soumis.');
            return $this->redirectToRoute('dossier_show', ['id' => $dossier->getId()]);
        }

        $dossier->submit();
        $this->em->flush();

        $this->addFlash('success', 'Dossier soumis avec succès.');
        return $this->redirectToRoute('dossier_show', ['id' => $dossier->getId()]);
    }

    /**
     * Liste des dossiers du client connecté
     */
    #[Route('/my/list', name: 'dossier_my_list', methods: ['GET'])]
    public function myDossiers(DossierRepository $repository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $customer = $user?->getCustomer();
        if (!$customer) {
            throw $this->createAccessDeniedException();
        }

        $dossiers = $repository->findBy(['customer' => $customer]);
        return $this->render('dossier/index.html.twig', ['dossiers' => $dossiers]);
    }

    // ========================= BACK-OFFICE ADMIN =========================

    /**
     * Liste des dossiers à traiter par l’admin
     */
    #[Route('/admin/list', name: 'admin_dossier_list')]
    public function adminList(DossierRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $dossiers = $repository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/dossier/list.html.twig', ['dossiers' => $dossiers]);
    }

    /**
     * Validation d’un dossier par l’admin
     */
    #[Route('/admin/{id}/approve', name: 'admin_dossier_approve', methods: ['POST'])]
    public function approve(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $dossier->approve();
        $this->em->flush();

        $this->addFlash('success', 'Dossier approuvé.');
        return $this->redirectToRoute('admin_dossier_list');
    }

    /**
     * Refus d’un dossier par l’admin
     */
    #[Route('/admin/{id}/reject', name: 'admin_dossier_reject', methods: ['POST'])]
    public function reject(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $dossier->reject();
        $this->em->flush();

        $this->addFlash('success', 'Dossier refusé.');
        return $this->redirectToRoute('admin_dossier_list');
    }
}
