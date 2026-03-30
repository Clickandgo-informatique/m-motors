<?php
// Gestion du login classique et Google OAuth

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Formulaire de login classique
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ]);
    }

    /**
     * Déconnexion (interceptée par Symfony)
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide, le firewall Symfony s’en occupe.');
    }

    /**
     * Redirection vers Google OAuth
     */
    #[Route('/connect/google', name: 'connect_google')]
    public function connect(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    /**
     * Callback Google OAuth
     * Intercepté par GoogleAuthenticator
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck()
    {
        // Cette méthode est vide, Symfony intercepte la route via l'authenticator
    }
}
