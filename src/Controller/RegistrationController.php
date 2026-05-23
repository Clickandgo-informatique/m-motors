<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use App\Service\CustomerCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * Route d'inscription
     * Affiche le formulaire et gère la création du compte
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        CustomerCodeGenerator $customerCodeGenerator
    ): Response {
        $user = new User();

        // Création du formulaire d'inscription
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Vérification du formulaire soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Hash du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            // Vérification de la checkbox 2FA
            if (!$form->get('accept2fa')->getData()) {
                // Ajoute une erreur au formulaire si l'utilisateur n'accepte pas le 2FA
                $form->addError(new \Symfony\Component\Form\FormError(
                    'Vous devez accepter l’activation du 2FA pour sécuriser votre compte.'
                ));
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Génération du secret TOTP (initialement non activé)
            $totpSecret = bin2hex(random_bytes(10)); // 20 caractères hexadécimaux
            $user->setTotpSecret($totpSecret);

            // Persistance du nouvel utilisateur en base
            $entityManager->persist($user);

            // Création automatique du Customer lié au User
            $customer = new \App\Entity\Customer();
            $customer->setUser($user);
            $customer->setEmail($user->getEmail());
            $customer->setFirstName('');
            $customer->setLastName('');
            
            //Génération du code client par défaut
            $customer->setCustomerCode($customerCodeGenerator->generateCustomerCode($customer->getLastName()));
            
            $entityManager->persist($customer);

            // liaison bidirectionnelle
            $user->setCustomer($customer);

            $entityManager->flush();

            // Envoi de l'email de confirmation
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email', // Nom de la route de vérification
                $user,
                (new TemplatedEmail())
                    ->from(new Address('register-verification@m-motors.com', 'M-Motors Mail Bot'))
                    ->to($user->getEmail())
                    ->subject('Veuillez confirmer votre email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // Flash message pour l'utilisateur
            $this->addFlash('info', 'Votre compte a été créé. Veuillez vérifier votre email et configurer votre 2FA.');

            // Redirection vers la page de configuration 2FA
            return $this->redirectToRoute('app_2fa_setup');
        }

        // Si le formulaire n'est pas soumis ou invalide, on l'affiche
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Route de vérification de l'email
     * Cette route est appelée par le lien dans l'email de confirmation
     */
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        Security $security
    ): Response {
        $id = $request->query->get('id');

        if (!$id) {
            $this->addFlash('danger', 'ID utilisateur manquant.');
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_register');
        }

        try {
            // Confirmation de l'email via EmailVerifier
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (\SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface $exception) {
            $this->addFlash('danger', 'Erreur lors de la vérification de votre email : ' . $exception->getReason());
            return $this->redirectToRoute('app_register');
        }

        // Email confirmé → login automatique
        $security->login($user, LoginFormAuthenticator::class, 'main');

        // Flash message pour l'utilisateur
        $this->addFlash('success', 'Votre email a été vérifié. Configurez maintenant votre 2FA.');

        // Redirection vers la page de configuration 2FA
        return $this->redirectToRoute('app_2fa_setup');
    }
}
