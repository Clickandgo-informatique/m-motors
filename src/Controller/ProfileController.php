<?php
// src/Controller/ProfileController.php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\QrCode\QrCodeGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile/2fa', name: 'profile_2fa')]
    #[IsGranted('ROLE_USER')]
    public function twoFactor(
        TotpAuthenticatorInterface $totpAuthenticator,
        QrCodeGeneratorInterface $qrCodeGenerator,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Générer le secret si TOTP non activé
        if (!$user->isTotpAuthenticationEnabled()) {
            $secret = $totpAuthenticator->generateSecret();
            $user->setTotpSecret($secret);
            $user->enableTotp();

            $em->persist($user);
            $em->flush();
        }

        // URL du QR code pour Google Authenticator
        $qrCodeUrl = $qrCodeGenerator->getUrl($user);

        return $this->render('profile/2fa.html.twig', [
            'qrCodeUrl' => $qrCodeUrl,
            'totpEnabled' => $user->isTotpAuthenticationEnabled(),
        ]);
    }
}
