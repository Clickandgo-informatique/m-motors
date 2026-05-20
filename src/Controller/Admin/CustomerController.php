<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Entity\User;
use App\Form\CustomerFormType;
use App\Repository\CustomerRepository;
use App\Service\CustomerCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    public function new(
        Request $request,
        CustomerCodeGenerator $codeGenerator,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        $customer = new Customer();
        $form = $this->createForm(CustomerFormType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 1. GENERATE CUSTOMER CODE
            $code = $codeGenerator->generateCustomerCode($customer->getLastName());
            $customer->setCustomerCode($code);

            // 2. CREATE USER
            $user = new User();
            $user->setEmail($customer->getEmail());
            $user->setRoles(['ROLE_CUSTOMER']);

            // 3. PASSWORD INIT BASED ON CODE
            $plainPassword = sprintf(
                '%s-%s',
                $code,
                bin2hex(random_bytes(3))
            );

            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword)
            );

            // 4. LINK
            $customer->setUser($user);

            // 5. PERSIST
            $em->persist($user);
            $em->persist($customer);
            $em->flush();

            // FLASH (admin only)
            $this->addFlash(
                'success',
                sprintf(
                    'Client créé. Code: %s | Mot de passe initial: %s',
                    $code,
                    $plainPassword
                )
            );

            return $this->redirectToRoute('customer_list');
        }

        return $this->render('admin/customer/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    // Édition d'un client existant

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


    // Suppression d'un client

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

    //Recherche d'un client pour autocomplete (dossier...)
    #[Route('/search', name: 'customer_search', methods: ['GET'])]
    public function search(
        Request $request,
        CustomerRepository $customerRepository
    ): JsonResponse {

        $query = trim((string) $request->query->get('customer'));

        if (mb_strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $query = mb_strtolower($query);

        $customers = $customerRepository->createQueryBuilder('c')
            ->where('LOWER(c.lastName) LIKE :q')
            ->orWhere('LOWER(c.firstName) LIKE :q')
            ->orWhere('LOWER(c.email) LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];

        foreach ($customers as $customer) {
            $results[] = [
                'id' => $customer->getId(),
                'label' => sprintf(
                    '%s %s (%s)',
                    $customer->getFirstName(),
                    $customer->getLastName(),
                    $customer->getEmail()
                )
            ];
        }

        if (empty($results)) {
            return new JsonResponse([
                [
                    'id' => null,
                    'label' => 'Aucun résultat'
                ]
            ]);
        }

        return new JsonResponse($results);
    }
}
