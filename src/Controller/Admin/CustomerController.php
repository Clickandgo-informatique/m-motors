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
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs et managers.');
        }

        $customers = $repository->findBy([], ['lastName' => 'ASC']);

        return $this->render('admin/customer/list.html.twig', [
            'customers' => $customers,
        ]);
    }

    /**
     * Création d'un client
     */
    #[Route('/new', name: 'customer_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CustomerCodeGenerator $codeGenerator,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $customer = new Customer();
        $form = $this->createForm(CustomerFormType::class, $customer);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $code = $codeGenerator->generateCustomerCode($customer->getLastName());
            $customer->setCustomerCode($code);

            $user = new User();
            $user->setEmail($customer->getEmail());
            $user->setRoles(['ROLE_CUSTOMER']);

            $plainPassword = sprintf(
                '%s-%s',
                $code,
                bin2hex(random_bytes(3))
            );

            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword)
            );

            $customer->setUser($user);

            $this->em->persist($user);
            $this->em->persist($customer);
            $this->em->flush();

            $this->addFlash(
                'success',
                sprintf(
                    'Client créé. Code: %s | Mot de passe: %s',
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

    /**
     * Edition client
     */
    #[Route('/{id}/edit', name: 'customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException();
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
     * Suppression client
     */
    #[Route('/{id}/delete', name: 'customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->request->get('_token'))) {
            $this->em->remove($customer);
            $this->em->flush();

            $this->addFlash('success', 'Client supprimé avec succès.');
        }

        return $this->redirectToRoute('customer_list');
    }

    /**
     * Affichage client
     */
    #[Route('/{id<\d+>}', name: 'customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('admin/customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    /**
     * API CRM AUTOCOMPLETE (UTILISÉ PAR DOSSIER)
     */
    #[Route('/api/customers/search', name: 'api_customers_search', methods: ['GET'])]
    public function searchCustomers(Request $request): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));

        if (mb_strlen($term) < 2) {
            return $this->json([]);
        }

        $repo = $this->em->getRepository(Customer::class);

        $qb = $repo->createQueryBuilder('c')
            ->where('LOWER(c.firstName) LIKE :q')
            ->orWhere('LOWER(c.lastName) LIKE :q')
            ->orWhere('LOWER(c.email) LIKE :q')
            ->orWhere('LOWER(c.customerCode) LIKE :q')
            ->setParameter('q', '%' . mb_strtolower($term) . '%')
            ->setMaxResults(10);

        $results = $qb->getQuery()->getResult();

        $data = array_map(fn(Customer $c) => [
            'id' => $c->getId(),
            'text' => sprintf(
                '[%s] %s %s',
                $c->getCustomerCode(),
                $c->getFirstName(),
                $c->getLastName()
            ),
        ], $results);

        return $this->json($data);
    }
}
