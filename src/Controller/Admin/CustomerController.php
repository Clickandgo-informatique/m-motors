<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Form\CustomerFormType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/customer')]
class CustomerController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Liste des clients
     */
    #[Route('/list', name: 'customer_list', methods: ['GET'])]
    public function list(CustomerRepository $repository): Response
    {
        // Autoriser ROLE_ADMIN ou ROLE_MANAGER
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs et managers.');
        }

        $customers = $repository->findBy([], ['lastName' => 'ASC']);
        return $this->render('admin/customer/list.html.twig', [
            'customers' => $customers,
        ]);
    }

    /**
     * Création d'un nouveau client
     */
    #[Route('/new', name: 'customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs et managers.');
        }

        $customer = new Customer();
        $form = $this->createForm(CustomerFormType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($customer);
            $this->em->flush();

            $this->addFlash('success', 'Client créé avec succès.');
            return $this->redirectToRoute('customer_list');
        }

        return $this->render('admin/customer/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Édition d'un client existant
     */
    #[Route('/{id}/edit', name: 'customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs et managers.');
        }

        $form = $this->createForm(CustomerFormType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Client modifié avec succès.');
            return $this->redirectToRoute('customer_list');
        }

        return $this->render('admin/customer/edit.html.twig', [
            'form' => $form->createView(),
            'customer' => $customer,
        ]);
    }

    /**
     * Suppression d'un client
     */
    #[Route('/{id}/delete', name: 'customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs et managers.');
        }

        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->request->get('_token'))) {
            $this->em->remove($customer);
            $this->em->flush();
            $this->addFlash('success', 'Client supprimé avec succès.');
        }

        return $this->redirectToRoute('customer_list');
    }

    /**
     * Affichage d’un client
     */
    #[Route('/{id}', name: 'customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs et managers.');
        }

        return $this->render('admin/customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }
}
