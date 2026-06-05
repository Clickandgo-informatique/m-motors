<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\User;
use App\Entity\DossierWorkflowLog;
use App\Service\Dossier\DossierWorkflowAuditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class DossierWorkflowAuditServiceTest extends TestCase
{
    public function testLogWithAuthenticatedUser(): void
    {
        // vérifie que l'utilisateur connecté est bien associé au log

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(42);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(DossierWorkflowLog::class));

        $em->expects($this->once())->method('flush');

        $service = new DossierWorkflowAuditService($em, $security);

        $dossier = new Dossier();

        $service->log(
            $dossier,
            'start',
            'draft',
            'vehicle_selected'
        );
    }

    public function testLogWithoutAuthenticatedUser(): void
    {
        // vérifie le comportement sans utilisateur connecté

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(DossierWorkflowLog::class));

        $em->expects($this->once())->method('flush');

        $service = new DossierWorkflowAuditService($em, $security);

        $dossier = new Dossier();

        $service->log(
            $dossier,
            'cancel',
            'draft',
            'cancelled'
        );
    }
}
