<?php
// TwoFactorController.php

namespace App\Controller;

use App\Entity\User;
use App\Service\TOTPService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/2fa', name: '2fa_')]
class TwoFactorController extends AbstractController
{
    #[Route('/verify', name: 'verify', methods: ['GET'])]
    public function showVerify(TOTPService $totpService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !$user->is2FAEnabled()) {
            $this->addFlash('warning', '2FA non activée pour cet utilisateur.');
            return $this->redirectToRoute('app_home');
        }

        // Génération du QR code via le service TOTPService
        $qrCodeSvg = $totpService->getQRCode($user);

        return $this->render('security/2fa_verify.html.twig', [
            'user' => $user,
            'qrCodeSvg' => $qrCodeSvg,
            'totpService' => $totpService
        ]);
    }

    #[Route('/check', name: 'check', methods: ['POST'])]
    public function verify(Request $request, SessionInterface $session, TOTPService $totpService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || !$user->is2FAEnabled()) {
            $this->addFlash('error', 'Utilisateur non authentifié ou 2FA non activée.');
            return $this->redirectToRoute('app_home');
        }

        $code = $request->request->get('totp_code', '');

        if ($totpService->verifyCode($user, $code)) {
            $session->set('2fa_passed', true);
            $this->addFlash('success', 'Authentification 2FA réussie.');

            // 🔹 Redirection selon rôle
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', 'Code 2FA invalide.');
        return $this->redirectToRoute('2fa_verify');
    }
}
