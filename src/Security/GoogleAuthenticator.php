<?php
// Authentificateur Google OAuth pour Symfony 7.4
// Crée un utilisateur si inexistant et gère le login OAuth

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GoogleAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {}

    /**
     * Détermine si la requête doit être gérée par cet authenticator
     */
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    /**
     * Authentifie l’utilisateur via Google
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $client->getAccessToken();

        return new SelfValidatingPassport(new UserBadge($this->getUserEmail($accessToken, $client)));
    }

    /**
     * Récupère ou crée l’utilisateur dans la base de données
     */
    private function getUserEmail($accessToken, GoogleClient $client): string
    {
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            // Création d’un nouvel utilisateur
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);

            // Génération d’un mot de passe aléatoire pour respecter NOT NULL
            $user->setPassword(
                $this->userPasswordHasher->hashPassword($user, bin2hex(random_bytes(16)))
            );

            $this->em->persist($user);
            $this->em->flush();
        }

        return $email;
    }

    /**
     * Redirection après succès d’authentification
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    /**
     * Redirection après échec d’authentification
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
