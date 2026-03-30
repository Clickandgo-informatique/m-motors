<?php
// src/Controller/TwoFactorController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticationHandlerInterface;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa', name: '2fa_login')]
    public function login(): Response
    {
        return $this->render('security/2fa_login.html.twig');
    }
}