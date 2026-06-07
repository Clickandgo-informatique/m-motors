<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\TwoFactorSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TwoFactorSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        // Vérifie l'enregistrement de l'événement kernel.request

        $this->assertSame(
            [
                'kernel.request' => 'onKernelRequest',
            ],
            TwoFactorSubscriber::getSubscribedEvents()
        );
    }

    public function testSkipsSubRequest(): void
    {
        // Vérifie que les sous-requêtes sont ignorées

        $security = $this->createMock(Security::class);
        $router = $this->createMock(RouterInterface::class);

        $subscriber = new TwoFactorSubscriber($security, $router);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testSkipsIfNoSession(): void
    {
        // Vérifie qu'aucun traitement n'est effectué sans session

        $security = $this->createMock(Security::class);
        $router = $this->createMock(RouterInterface::class);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = new Request();
        $request->attributes->set('_route', 'admin_dashboard');

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber = new TwoFactorSubscriber($security, $router);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testSkipsAllowedRoute(): void
    {
        // Vérifie qu'une route non protégée n'est jamais bloquée

        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void {}

            public function getUserIdentifier(): string
            {
                return 'user';
            }

            public function is2FAEnabled(): bool
            {
                return true;
            }
        };

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $router = $this->createMock(RouterInterface::class);

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn(true);

        $request = new Request();
        $request->attributes->set('_route', 'app_home');
        $request->setSession($session);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber = new TwoFactorSubscriber($security, $router);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testRedirectsWhen2FANotPassed(): void
    {
        // Vérifie la redirection lorsqu'une route protégée nécessite la 2FA

        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void {}

            public function getUserIdentifier(): string
            {
                return 'user';
            }

            public function is2FAEnabled(): bool
            {
                return true;
            }
        };

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')
            ->with('2fa_verify')
            ->willReturn('/2fa');

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('2fa_passed', false)
            ->willReturn(false);

        $request = new Request();
        $request->attributes->set('_route', 'admin_dashboard');
        $request->setSession($session);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber = new TwoFactorSubscriber($security, $router);

        $subscriber->onKernelRequest($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame('/2fa', $event->getResponse()->headers->get('Location'));
    }

    public function testSkipsWhenRouteIsMissing(): void
    {
        // Vérifie qu'aucun traitement n'est effectué sans route

        $security = $this->createMock(Security::class);
        $router = $this->createMock(RouterInterface::class);

        $session = $this->createMock(SessionInterface::class);

        $request = new Request();
        $request->setSession($session);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber = new TwoFactorSubscriber($security, $router);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testSkipsWhenUserIsNotAuthenticated(): void
    {
        // Vérifie qu'aucun traitement n'est effectué sans utilisateur connecté

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $router = $this->createMock(RouterInterface::class);

        $session = $this->createMock(SessionInterface::class);

        $request = new Request();
        $request->attributes->set('_route', 'admin_dashboard');
        $request->setSession($session);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber = new TwoFactorSubscriber($security, $router);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testSkipsWhen2FAIsDisabled(): void
    {
        // Vérifie que la requête passe lorsque la 2FA n'est pas activée

        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void {}

            public function getUserIdentifier(): string
            {
                return 'user';
            }

            public function is2FAEnabled(): bool
            {
                return false;
            }
        };

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $router = $this->createMock(RouterInterface::class);

        $session = $this->createMock(SessionInterface::class);

        $request = new Request();
        $request->attributes->set('_route', 'admin_dashboard');
        $request->setSession($session);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber = new TwoFactorSubscriber($security, $router);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testAllowsRequestWhen2FAAlreadyPassed(): void
    {
        // Vérifie qu'aucune redirection n'est effectuée après validation 2FA

        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void {}

            public function getUserIdentifier(): string
            {
                return 'user';
            }

            public function is2FAEnabled(): bool
            {
                return true;
            }
        };

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $router = $this->createMock(RouterInterface::class);

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('2fa_passed', false)
            ->willReturn(true);

        $request = new Request();
        $request->attributes->set('_route', 'admin_dashboard');
        $request->setSession($session);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber = new TwoFactorSubscriber($security, $router);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }
}
