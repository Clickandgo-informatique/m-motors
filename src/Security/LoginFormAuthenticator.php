<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;



class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    private RouterInterface $router;
    private EntityManagerInterface $em;
    private RequestStack $requestStack;

    public function __construct(
        RouterInterface $router,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,     
       
    ) {
        $this->router = $router;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
       
    }

    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private UrlGeneratorInterface $urlGenerator;



    public function authenticate(Request $request): Passport
    {
        $email = $request->get('email', '');
        $password = $request->get('password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token', '')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {

        /** @var User $user */
        $user = $token->getUser();

        // RESET 2FA à chaque login
        $request->getSession()->set('2fa_passed', false);

        // Si pas encore de secret 2FA → on le génère
        if (!$user->getGoogle2FASecret()) {
            $google2FA = new \PragmaRX\Google2FA\Google2FA();
            $secret = $google2FA->generateSecretKey();
            $user->setGoogle2FASecret($secret);
            $user->setIs2FAEnabled(false);

            $this->em->persist($user);
            $this->em->flush();
        }

        // Si l’utilisateur n’a pas encore validé son 2FA → setup obligatoire
        if (!$user->is2FAEnabled()) {
            return new RedirectResponse($this->router->generate('app_2fa_setup'));
        }

        // Sinon, passage obligatoire par la vérification OTP
        $request->getSession()->set('2fa:userId', $user->getId());
        return new RedirectResponse($this->router->generate('2fa_verify'));
    }

    //Si mot de passe ou email invalides on affiche un flashbag d'erreur
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(
            SecurityRequestAttributes::AUTHENTICATION_ERROR,
            new AuthenticationException('Adresse email ou mot de passe incorrect.')
        );

        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
