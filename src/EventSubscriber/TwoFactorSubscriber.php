<?php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class TwoFactorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private RouterInterface $router
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ne traiter que la requête principale
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Session obligatoire pour stocker l'état 2FA
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        // Récupération de la route actuelle
        $route = $request->attributes->get('_route');

        if (!$route) {
            return;
        }

        // Pas d'utilisateur connecté => pas de 2FA
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        // Zones protégées par 2FA (uniquement admin / actions sensibles)
        // On utilise des préfixes pour éviter de devoir lister toutes les routes
        $protectedRoutePrefixes = [
            'admin',
            'dossier',
            'account',
            'profile',
            'vehicle_manage',
        ];

        $isProtectedRoute = false;

        foreach ($protectedRoutePrefixes as $prefix) {
            if (str_starts_with((string) $route, $prefix)) {
                $isProtectedRoute = true;
                break;
            }
        }

        // Si la route n'est pas protégée, on ne bloque jamais
        if (!$isProtectedRoute) {
            return;
        }

        // Si l'utilisateur n'a pas activé la 2FA, on ignore la logique
        if (!method_exists($user, 'is2FAEnabled') || !$user->is2FAEnabled()) {
            return;
        }

        // Si la 2FA a déjà été validée dans la session, on laisse passer
        if ($session->get('2fa_passed', false)) {
            return;
        }

        // Sinon redirection vers la vérification 2FA
        $event->setResponse(
            new RedirectResponse(
                $this->router->generate('2fa_verify')
            )
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
