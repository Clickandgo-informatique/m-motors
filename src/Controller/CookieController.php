<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cookies', name: 'app_cookies_')]
class CookieController extends AbstractController
{
    #[Route('/accept', name: 'accept', methods: ['POST'])]
    public function accept(): JsonResponse
    {
        $response = new JsonResponse([
            'success' => true,
        ]);

        $response->headers->setCookie(
            Cookie::create(
                'm_motors_cookie_consent',
                json_encode([
                    'analytics' => true,
                    'marketing' => true,
                ]),
                new \DateTimeImmutable('+6 months')
            )
        );

        return $response;
    }

    #[Route('/refuse', name: 'refuse', methods: ['POST'])]
    public function refuse(): JsonResponse
    {
        $response = new JsonResponse([
            'success' => true,
        ]);

        $response->headers->setCookie(
            Cookie::create(
                'm_motors_cookie_consent',
                json_encode([
                    'analytics' => false,
                    'marketing' => false,
                ]),
                new \DateTimeImmutable('+6 months')
            )
        );

        return $response;
    }

    #[Route('/preferences', name: 'preferences', methods: ['POST'])]
    public function preferences(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $analytics = (bool) ($data['analytics'] ?? false);
        $marketing = (bool) ($data['marketing'] ?? false);

        $response = new JsonResponse([
            'success' => true,
        ]);

        $response->headers->setCookie(
            Cookie::create(
                'm_motors_cookie_consent',
                json_encode([
                    'analytics' => $analytics,
                    'marketing' => $marketing,
                ]),
                new \DateTimeImmutable('+6 months')
            )
        );

        return $response;
    }
}
