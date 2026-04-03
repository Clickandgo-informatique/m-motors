<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TwoFactorSetupController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/2fa/setup', name: '2fa_setup')]
    public function setup(): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Générer un secret TOTP si absent
        if (!$user->getTotpSecret()) {
            $user->setTotpSecret(TotpAuthenticator::generateSecret());
            // ⚠️ penser à persister en base
        }

        $totpSecret = $user->getTotpSecret();

        // URI compatible Google Authenticator
        $totpUri = 'otpauth://totp/' . urlencode($user->getEmail())
            . '?secret=' . $totpSecret . '&issuer=MonApp';

        // Création QR code avec Endroid QRCode <6.x
        $qrCode = new QrCode($totpUri);
        $qrCode->setSize(200);

        $writer = new PngWriter();
        $qrImage = $writer->write($qrCode);

        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrImage->getString());

        return $this->render('security/2fa_setup.html.twig', [
            'qrCode' => $qrCodeBase64,
        ]);
    }
}
