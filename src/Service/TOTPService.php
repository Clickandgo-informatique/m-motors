<?php

namespace App\Service;

use App\Entity\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd; // <- SVG backend
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TOTPService
{
    private Google2FA $google2FA;

    public function __construct()
    {
        $this->google2FA = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey();
    }

    public function getQRCode(User $user, int $size = 200): string
    {
        $otpUri = $this->google2FA->getQRCodeUrl(
            'm-motors',
            $user->getEmail(),
            $user->getGoogle2FASecret()
        );

        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd() // <- aucun problème IDE, fonctionne partout
        );

        $writer = new Writer($renderer);
        $svgString = $writer->writeString($otpUri);

        return $svgString;
    }

    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->getGoogle2FASecret()) {
            return false;
        }

        return $this->google2FA->verifyKey($user->getGoogle2FASecret(), $code, 4);
    }
}
