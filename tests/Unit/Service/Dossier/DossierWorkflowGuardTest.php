<?php

namespace App\Tests\Unit\Service\Dossier;

use App\Entity\Dossier;
use App\Service\Dossier\DossierWorkflowGuard;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class DossierWorkflowGuardTest extends TestCase
{
    public function testCanReturnsTrue(): void
    {
        // vérifie que le guard délègue correctement à Symfony Workflow

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);

        $guard = new DossierWorkflowGuard($workflow, $logger);

        $dossier = new Dossier();

        $this->assertTrue($guard->can($dossier, 'test_transition'));
    }

    public function testAssertCanPassesWhenAllowed(): void
    {
        // vérifie que assertCan ne bloque pas une transition valide

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $guard = new DossierWorkflowGuard($workflow, $logger);

        $dossier = new Dossier();

        $guard->assertCan($dossier, 'valid_transition');

        $this->assertTrue(true);
    }

    public function testAssertCanThrowsAndLogs(): void
    {
        // vérifie qu'une transition invalide déclenche exception + log

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('can')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Workflow error'),
                $this->arrayHasKey('dossierId')
            );

        $guard = new DossierWorkflowGuard($workflow, $logger);

        $dossier = new Dossier();

        $this->expectException(\LogicException::class);

        $guard->assertCan($dossier, 'invalid_transition');
    }

    public function testApplyExecutesTransitionAndCallback(): void
    {
        // vérifie apply + exécution du callback après transition

        $workflow = $this->createMock(WorkflowInterface::class);

        $workflow->method('can')->willReturn(true);

        $workflow->expects($this->once())
            ->method('apply')
            ->with($this->isInstanceOf(Dossier::class), 'transition');

        $logger = $this->createMock(LoggerInterface::class);

        $guard = new DossierWorkflowGuard($workflow, $logger);

        $dossier = new Dossier();

        $callbackCalled = false;

        $guard->apply($dossier, 'transition', function ($d) use (&$callbackCalled) {
            $callbackCalled = true;
        });

        $this->assertTrue($callbackCalled);
    }
}
