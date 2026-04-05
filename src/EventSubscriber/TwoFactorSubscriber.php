<?php
// src/EventSubscriber/TwoFactorSubscriber.php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;


class TwoFactorSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private RouterInterface $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // ✅ uniquement requête principale
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$session) {
            return;
        }

        // ✅ attendre que la route soit connue
        $route = $request->attributes->get('_route');
        if (!$route) {
            return;
        }

        // ✅ utilisateur connecté ?
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        // 🔓 routes autorisées SANS 2FA
        $allowedRoutes = [
            'app_home',
            'app_login',
            'app_logout',
            '2fa_verify',
            '2fa_check',
            'app_2fa_setup',
            '_wdt', // toolbar Symfony
            '_profiler',
        ];

        if (in_array($route, $allowedRoutes, true)) {
            return;
        }

        // ✅ si 2FA pas activée → on laisse passer
        if (!method_exists($user, 'is2FAEnabled') || !$user->is2FAEnabled()) {
            return;
        }

        // 🔥 si 2FA pas validée → redirection
        if (!$session->get('2fa_passed', false)) {
            $event->setResponse(
                new RedirectResponse($this->router->generate('2fa_verify'))
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
