<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;

class TwoFactorController extends AbstractController
{
    private EntityManagerInterface $em;
    private TotpAuthenticatorInterface $totpAuthenticator;
    private UserAuthenticatorInterface $userAuthenticator;

    public function __construct(
        EntityManagerInterface $em,
        TotpAuthenticatorInterface $totpAuthenticator,
        UserAuthenticatorInterface $userAuthenticator
    ) {
        $this->em = $em;
        $this->totpAuthenticator = $totpAuthenticator;
        $this->userAuthenticator = $userAuthenticator;
    }

    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    public function setup(Request $request, LoginFormAuthenticator $authenticator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez vous connecter pour configurer le 2FA.');
            return $this->redirectToRoute('app_login');
        }

        // Génération QR code si non présent
        if (!$user->getTotpSecret()) {
            $totpSecret = bin2hex(random_bytes(10));
            $user->setTotpSecret($totpSecret);
            $this->em->flush();
        }

        $qrCode = $this->totpAuthenticator->getQRContent($user);

        // Vérification du code TOTP
        if ($request->isMethod('POST')) {
            $code = $request->request->get('_auth_code');
            if ($this->totpAuthenticator->checkCode($user, $code)) {
                $user->enableTotp();
                $this->em->flush();

                $this->addFlash('success', '2FA activé avec succès !');

                return $this->userAuthenticator->authenticateUser(
                    $user,
                    $authenticator,
                    $request
                );
            } else {
                $this->addFlash('danger', 'Code invalide, veuillez réessayer.');
            }
        }

        return $this->render('security/2fa_setup.html.twig', [
            'qrCode' => $qrCode,
        ]);
    }

    #[Route('/2fa/login', name: 'app_2fa_login')]
    public function login(): Response
    {
        // Page login 2FA (après que l'utilisateur ait activé son 2FA)
        return $this->render('security/2fa_login.html.twig');
    }
}
