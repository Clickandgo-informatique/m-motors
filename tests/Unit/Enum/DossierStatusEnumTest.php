<?php

namespace App\Tests\Unit\Enum;

use App\Enum\DossierStatus;
use PHPUnit\Framework\TestCase;

class DossierStatusEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie les libellés de tous les statuts

        $this->assertSame('créé', DossierStatus::CREATED->label());
        $this->assertSame('en cours', DossierStatus::IN_PROGRESS->label());
        $this->assertSame('en attente de validation', DossierStatus::PENDING_VALIDATION->label());
        $this->assertSame('validé', DossierStatus::VALIDATED->label());
        $this->assertSame('refusé', DossierStatus::REJECTED->label());
        $this->assertSame('en attente de paiement', DossierStatus::PENDING_PAYMENT->label());
        $this->assertSame('paiement partiel', DossierStatus::PARTIALLY_PAID->label());
        $this->assertSame('payé', DossierStatus::PAID->label());
        $this->assertSame('prêt à livrer', DossierStatus::READY_FOR_DELIVERY->label());
        $this->assertSame('livré', DossierStatus::DELIVERED->label());
        $this->assertSame('clôturé', DossierStatus::CLOSED->label());
    }

    public function testIcons(): void
    {
        // vérifie les icônes associées à chaque statut

        $this->assertSame(
            'fa-solid fa-file-circle-plus',
            DossierStatus::CREATED->icon()
        );

        $this->assertSame(
            'fa-solid fa-spinner',
            DossierStatus::IN_PROGRESS->icon()
        );

        $this->assertSame(
            'fa-solid fa-hourglass-half',
            DossierStatus::PENDING_VALIDATION->icon()
        );

        $this->assertSame(
            'fa-solid fa-check',
            DossierStatus::VALIDATED->icon()
        );

        $this->assertSame(
            'fa-solid fa-xmark',
            DossierStatus::REJECTED->icon()
        );

        $this->assertSame(
            'fa-solid fa-credit-card',
            DossierStatus::PENDING_PAYMENT->icon()
        );

        $this->assertSame(
            'fa-solid fa-coins',
            DossierStatus::PARTIALLY_PAID->icon()
        );

        $this->assertSame(
            'fa-solid fa-circle-check',
            DossierStatus::PAID->icon()
        );

        $this->assertSame(
            'fa-solid fa-truck',
            DossierStatus::READY_FOR_DELIVERY->icon()
        );

        $this->assertSame(
            'fa-solid fa-flag-checkered',
            DossierStatus::DELIVERED->icon()
        );

        $this->assertSame(
            'fa-solid fa-lock',
            DossierStatus::CLOSED->icon()
        );
    }

    public function testBadges(): void
    {
        // vérifie les classes bootstrap

        $this->assertSame('secondary', DossierStatus::CREATED->badge());
        $this->assertSame('info', DossierStatus::IN_PROGRESS->badge());
        $this->assertSame('warning', DossierStatus::PENDING_VALIDATION->badge());
        $this->assertSame('primary', DossierStatus::VALIDATED->badge());
        $this->assertSame('danger', DossierStatus::REJECTED->badge());
        $this->assertSame('warning', DossierStatus::PENDING_PAYMENT->badge());
        $this->assertSame('info', DossierStatus::PARTIALLY_PAID->badge());
        $this->assertSame('success', DossierStatus::PAID->badge());
        $this->assertSame('primary', DossierStatus::READY_FOR_DELIVERY->badge());
        $this->assertSame('success', DossierStatus::DELIVERED->badge());
        $this->assertSame('dark', DossierStatus::CLOSED->badge());
    }

    public function testActiveStatuses(): void
    {
        // vérifie les statuts considérés comme actifs

        $this->assertTrue(DossierStatus::CREATED->isActive());
        $this->assertTrue(DossierStatus::IN_PROGRESS->isActive());
        $this->assertTrue(DossierStatus::PENDING_VALIDATION->isActive());
        $this->assertTrue(DossierStatus::VALIDATED->isActive());
        $this->assertTrue(DossierStatus::PENDING_PAYMENT->isActive());
        $this->assertTrue(DossierStatus::PARTIALLY_PAID->isActive());
        $this->assertTrue(DossierStatus::PAID->isActive());
        $this->assertTrue(DossierStatus::READY_FOR_DELIVERY->isActive());

        $this->assertFalse(DossierStatus::REJECTED->isActive());
        $this->assertFalse(DossierStatus::DELIVERED->isActive());
        $this->assertFalse(DossierStatus::CLOSED->isActive());
    }

    public function testFinalStatuses(): void
    {
        // vérifie les statuts finaux

        $this->assertTrue(DossierStatus::DELIVERED->isFinal());
        $this->assertTrue(DossierStatus::CLOSED->isFinal());

        $this->assertFalse(DossierStatus::CREATED->isFinal());
        $this->assertFalse(DossierStatus::PAID->isFinal());
    }

    public function testBlockedStatuses(): void
    {
        // vérifie les statuts bloquants

        $this->assertTrue(DossierStatus::REJECTED->isBlocked());
        $this->assertTrue(DossierStatus::PENDING_PAYMENT->isBlocked());

        $this->assertFalse(DossierStatus::VALIDATED->isBlocked());
        $this->assertFalse(DossierStatus::PAID->isBlocked());
    }

    public function testPaidStatuses(): void
    {
        // vérifie les statuts considérés comme payés

        $this->assertTrue(DossierStatus::PARTIALLY_PAID->isPaid());
        $this->assertTrue(DossierStatus::PAID->isPaid());

        $this->assertFalse(DossierStatus::PENDING_PAYMENT->isPaid());
        $this->assertFalse(DossierStatus::CREATED->isPaid());
    }

    public function testTransitionFromCreated(): void
    {
        // vérifie les transitions depuis créé

        $this->assertTrue(
            DossierStatus::CREATED->canTransitionTo(
                DossierStatus::IN_PROGRESS
            )
        );

        $this->assertTrue(
            DossierStatus::CREATED->canTransitionTo(
                DossierStatus::PENDING_VALIDATION
            )
        );

        $this->assertFalse(
            DossierStatus::CREATED->canTransitionTo(
                DossierStatus::PAID
            )
        );
    }

    public function testTransitionFromInProgress(): void
    {
        // vérifie les transitions depuis en cours

        $this->assertTrue(
            DossierStatus::IN_PROGRESS->canTransitionTo(
                DossierStatus::PENDING_VALIDATION
            )
        );

        $this->assertTrue(
            DossierStatus::IN_PROGRESS->canTransitionTo(
                DossierStatus::REJECTED
            )
        );

        $this->assertFalse(
            DossierStatus::IN_PROGRESS->canTransitionTo(
                DossierStatus::PAID
            )
        );
    }

    public function testTransitionFromPendingValidation(): void
    {
        // vérifie les transitions depuis attente validation

        $this->assertTrue(
            DossierStatus::PENDING_VALIDATION->canTransitionTo(
                DossierStatus::VALIDATED
            )
        );

        $this->assertTrue(
            DossierStatus::PENDING_VALIDATION->canTransitionTo(
                DossierStatus::REJECTED
            )
        );

        $this->assertFalse(
            DossierStatus::PENDING_VALIDATION->canTransitionTo(
                DossierStatus::PAID
            )
        );
    }

    public function testTransitionWorkflow(): void
    {
        // vérifie le workflow principal complet

        $this->assertTrue(
            DossierStatus::VALIDATED->canTransitionTo(
                DossierStatus::PENDING_PAYMENT
            )
        );

        $this->assertTrue(
            DossierStatus::PENDING_PAYMENT->canTransitionTo(
                DossierStatus::PARTIALLY_PAID
            )
        );

        $this->assertTrue(
            DossierStatus::PENDING_PAYMENT->canTransitionTo(
                DossierStatus::PAID
            )
        );

        $this->assertTrue(
            DossierStatus::PARTIALLY_PAID->canTransitionTo(
                DossierStatus::PAID
            )
        );

        $this->assertTrue(
            DossierStatus::PAID->canTransitionTo(
                DossierStatus::READY_FOR_DELIVERY
            )
        );

        $this->assertTrue(
            DossierStatus::READY_FOR_DELIVERY->canTransitionTo(
                DossierStatus::DELIVERED
            )
        );

        $this->assertTrue(
            DossierStatus::DELIVERED->canTransitionTo(
                DossierStatus::CLOSED
            )
        );
    }

    public function testClosedCannotTransition(): void
    {
        // un dossier clôturé ne peut plus évoluer

        $this->assertFalse(
            DossierStatus::CLOSED->canTransitionTo(
                DossierStatus::CREATED
            )
        );

        $this->assertFalse(
            DossierStatus::CLOSED->canTransitionTo(
                DossierStatus::PAID
            )
        );
    }

    public function testRejectedCannotTransition(): void
    {
        // un dossier refusé est bloqué définitivement

        $this->assertFalse(
            DossierStatus::REJECTED->canTransitionTo(
                DossierStatus::VALIDATED
            )
        );

        $this->assertFalse(
            DossierStatus::REJECTED->canTransitionTo(
                DossierStatus::CLOSED
            )
        );
    }
}
