<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\Customer;
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
     * URL : /dossier/create/{id}/{type}
     *
     * @param Vehicle $vehicle
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

        /** @var Customer|null $customer */
        $customer = $user->getCustomer();
        if (!$customer) {
            $customer = new Customer();
            $customer->setUser($user);

            $nickname = $user->getNickname();
            if ($nickname) {
                $names = explode(' ', $nickname, 2);
                $customer->setFirstname($names[0] ?? 'Client');
                $customer->setLastname($names[1] ?? 'SansNom');
            } else {
                $email = $user->getEmail();
                $customer->setFirstname('Client');
                $customer->setLastname(strstr($email, '@', true));
            }

            $this->em->persist($customer);
            $this->em->flush();
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
     * Liste des dossiers du client connecté
     * URL : /dossier/my/list
     */
    #[Route('/my/list', name: 'dossier_my_list', methods: ['GET'])]
    public function myDossiers(DossierRepository $repository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        /** @var Customer|null $customer */
        $customer = $user?->getCustomer();
        if (!$customer) {
            throw $this->createAccessDeniedException();
        }

        $dossiers = $repository->findBy(['customer' => $customer]);
        return $this->render('dossier/index.html.twig', ['dossiers' => $dossiers]);
    }

    /**
     * Affiche un dossier client
     * URL : /dossier/{id}
     */
    #[Route('/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('view', $dossier);
        return $this->render('dossier/show.html.twig', ['dossier' => $dossier]);
    }

    /**
     * Soumission d’un dossier
     * URL : /dossier/{id}/submit
     */
    #[Route('/{id<\d+>}/submit', name: 'dossier_submit', methods: ['POST'])]
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

    // ========================= BACK-OFFICE ADMIN =========================
    // Routes admin préfixées /dossier/admin/ pour éviter tout conflit avec front

    /**
     * Liste des dossiers à traiter par l’admin
     * URL : /dossier/admin/list
     */
    #[Route('/admin/list', name: 'dossier_list')]
    public function adminList(DossierRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $dossiers = $repository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/dossier/list.html.twig', ['dossiers' => $dossiers]);
    }

    /**
     * Création d'un nouveau dossier via formulaire admin
     * URL : /dossier/admin/new
     */
    #[Route('/admin/new', name: 'dossier_new')]
    public function new(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/dossier/edit.html.twig', [
            'vehicles' => $this->em->getRepository(Vehicle::class)->findAll(),
            'title' => 'Créer un dossier'
        ]);
    }

    /**
     * Validation d’un dossier par l’admin
     * URL : /dossier/admin/{id}/approve
     */
    #[Route('/admin/{id<\d+>}/approve', name: 'admin_dossier_approve', methods: ['POST'])]
    public function approve(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $dossier->approve();
        $this->em->flush();

        $this->addFlash('success', 'Dossier approuvé.');
        return $this->redirectToRoute('dossier_list');
    }

    /**
     * Refus d’un dossier par l’admin
     * URL : /dossier/admin/{id}/reject
     */
    #[Route('/admin/{id<\d+>}/reject', name: 'admin_dossier_reject', methods: ['POST'])]
    public function reject(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $dossier->reject();
        $this->em->flush();

        $this->addFlash('success', 'Dossier refusé.');
        return $this->redirectToRoute('dossier_list');
    }
}
