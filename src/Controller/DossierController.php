<?php

namespace App\Controller;

use App\Entity\Customer;
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

#[Route('/dossier')]
class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    // ---------------------------------------------------------------------
    // FRONT CLIENT
    // ---------------------------------------------------------------------

    /**
     * Création d'un dossier à partir d'un véhicule et d'un type.
     *
     * IMPORTANT :
     * Cette action modifie des données, elle doit être appelée en POST
     * pour respecter les bonnes pratiques HTTP et éviter les erreurs Turbo.
     *
     * @param Vehicle $vehicle
     * @param string $type
     */
    #[Route('/create/{id}/{type}', name: 'dossier_create', methods: ['POST'])]
    public function createFromVehicle(Request $request, Vehicle $vehicle, string $type): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Redirection si utilisateur non connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupération ou création du customer lié à l'utilisateur
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

        // Validation du type de dossier via enum
        $dossierType = DossierType::tryFrom($type);

        if (!$dossierType) {
            throw $this->createNotFoundException('Type de dossier invalide.');
        }

        // Création du dossier
        $dossier = new Dossier();
        $dossier->setCustomer($customer)
            ->setVehicle($vehicle)
            ->setType($dossierType);

        $this->em->persist($dossier);
        $this->em->flush();

        // Redirection obligatoire après POST (compatibilité Turbo + bonnes pratiques HTTP)
        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    /**
     * Liste des dossiers de l'utilisateur connecté.
     */
    #[Route('/my/list', name: 'dossier_my_list', methods: ['GET'])]
    public function myDossiers(DossierRepository $repository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $customer = $user?->getCustomer();

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
     * Affichage d'un dossier.
     */
    #[Route('/{id<\d+>}', name: 'dossier_show', methods: ['GET'])]
    public function show(Dossier $dossier): Response
    {
        // Vérification des droits d'accès via voter
        $this->denyAccessUnlessGranted('view', $dossier);

        return $this->render('dossier/show.html.twig', [
            'dossier' => $dossier
        ]);
    }

    /**
     * Soumission d'un dossier (passage de draft à soumis).
     */
    #[Route('/{id<\d+>}/submit', name: 'dossier_submit', methods: ['POST'])]
    public function submit(Dossier $dossier): Response
    {
        $this->denyAccessUnlessGranted('edit', $dossier);

        // Vérifie si le dossier est déjà soumis
        if (!$dossier->isDraft()) {
            $this->addFlash('error', 'Dossier déjà soumis.');

            return $this->redirectToRoute('dossier_show', [
                'id' => $dossier->getId()
            ]);
        }

        // Soumission du dossier
        $dossier->submit();
        $this->em->flush();

        $this->addFlash('success', 'Dossier soumis avec succès.');

        return $this->redirectToRoute('dossier_show', [
            'id' => $dossier->getId()
        ]);
    }

    // ---------------------------------------------------------------------
    // BACK-OFFICE ADMIN
    // ---------------------------------------------------------------------

    /**
     * Liste des dossiers côté administration.
     */
    #[Route('/admin/list', name: 'dossier_list', methods: ['GET'])]
    public function adminList(DossierRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $dossiers = $repository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render('admin/dossier/list.html.twig', [
            'dossiers' => $dossiers
        ]);
    }

    /**
     * Création d'un dossier côté admin via formulaire Symfony.
     */
    #[Route('/admin/new', name: 'dossier_new', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('dossier_list');
        }

        return $this->render('admin/dossier/new.html.twig', [
            'form' => $form->createView(),
            'title' => 'Créer un dossier'
        ]);
    }

    /**
     * Approbation d'un dossier par l'administration.
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
     * Refus d'un dossier par l'administration.
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
