<?php

namespace App\Tests\Unit\Enum;

use App\Enum\VehicleStatus;
use PHPUnit\Framework\TestCase;

class VehicleStatusEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie les libellés des statuts

        $this->assertSame(
            'Disponible (vente)',
            VehicleStatus::AVAILABLE_FOR_SALE->label()
        );

        $this->assertSame(
            'Disponible (location)',
            VehicleStatus::AVAILABLE_FOR_RENT->label()
        );

        $this->assertSame(
            'Réservé',
            VehicleStatus::RESERVED->label()
        );

        $this->assertSame(
            'Loué',
            VehicleStatus::RENTED->label()
        );

        $this->assertSame(
            'Vendu',
            VehicleStatus::SOLD->label()
        );

        $this->assertSame(
            'Commandé',
            VehicleStatus::ORDERED->label()
        );

        $this->assertSame(
            'En maintenance',
            VehicleStatus::MAINTENANCE->label()
        );
    }

    public function testIsAvailable(): void
    {
        // vérifie les statuts disponibles

        $this->assertTrue(
            VehicleStatus::AVAILABLE_FOR_SALE->isAvailable()
        );

        $this->assertTrue(
            VehicleStatus::AVAILABLE_FOR_RENT->isAvailable()
        );

        $this->assertFalse(
            VehicleStatus::RESERVED->isAvailable()
        );
    }

    public function testIsReserved(): void
    {
        // vérifie la détection d'un véhicule réservé

        $this->assertTrue(
            VehicleStatus::RESERVED->isReserved()
        );

        $this->assertFalse(
            VehicleStatus::SOLD->isReserved()
        );
    }

    public function testIsSold(): void
    {
        // vérifie la détection d'un véhicule vendu

        $this->assertTrue(
            VehicleStatus::SOLD->isSold()
        );

        $this->assertFalse(
            VehicleStatus::AVAILABLE_FOR_SALE->isSold()
        );
    }

    public function testIsRented(): void
    {
        // vérifie la détection d'un véhicule loué

        $this->assertTrue(
            VehicleStatus::RENTED->isRented()
        );

        $this->assertFalse(
            VehicleStatus::AVAILABLE_FOR_RENT->isRented()
        );
    }

    public function testIsVisible(): void
    {
        // vérifie les statuts visibles dans le catalogue

        $this->assertTrue(
            VehicleStatus::AVAILABLE_FOR_SALE->isVisible()
        );

        $this->assertTrue(
            VehicleStatus::AVAILABLE_FOR_RENT->isVisible()
        );

        $this->assertTrue(
            VehicleStatus::RESERVED->isVisible()
        );

        $this->assertFalse(
            VehicleStatus::RENTED->isVisible()
        );

        $this->assertFalse(
            VehicleStatus::SOLD->isVisible()
        );
    }

    public function testBadges(): void
    {
        // vérifie les classes bootstrap

        $this->assertSame(
            'success',
            VehicleStatus::AVAILABLE_FOR_SALE->badge()
        );

        $this->assertSame(
            'primary',
            VehicleStatus::AVAILABLE_FOR_RENT->badge()
        );

        $this->assertSame(
            'warning text-dark',
            VehicleStatus::RESERVED->badge()
        );

        $this->assertSame(
            'info',
            VehicleStatus::RENTED->badge()
        );

        $this->assertSame(
            'dark',
            VehicleStatus::SOLD->badge()
        );

        $this->assertSame(
            'secondary',
            VehicleStatus::ORDERED->badge()
        );

        $this->assertSame(
            'danger',
            VehicleStatus::MAINTENANCE->badge()
        );
    }

    public function testReserve(): void
    {
        // vérifie le changement vers réservé

        $this->assertSame(
            VehicleStatus::RESERVED,
            VehicleStatus::AVAILABLE_FOR_SALE->reserve()
        );
    }

    public function testMarkAsSold(): void
    {
        // vérifie le changement vers vendu

        $this->assertSame(
            VehicleStatus::SOLD,
            VehicleStatus::AVAILABLE_FOR_SALE->markAsSold()
        );
    }

    public function testMarkAsRented(): void
    {
        // vérifie le changement vers loué

        $this->assertSame(
            VehicleStatus::RENTED,
            VehicleStatus::AVAILABLE_FOR_RENT->markAsRented()
        );
    }

    public function testMakeAvailable(): void
    {
        // vérifie le retour à la disponibilité

        $this->assertSame(
            VehicleStatus::AVAILABLE_FOR_RENT,
            VehicleStatus::RENTED->makeAvailable()
        );

        $this->assertSame(
            VehicleStatus::AVAILABLE_FOR_RENT,
            VehicleStatus::AVAILABLE_FOR_RENT->makeAvailable()
        );

        $this->assertSame(
            VehicleStatus::AVAILABLE_FOR_SALE,
            VehicleStatus::RESERVED->makeAvailable()
        );
    }

    public function testTransitions(): void
    {
        // vérifie plusieurs transitions métier

        $this->assertTrue(
            VehicleStatus::AVAILABLE_FOR_SALE
                ->canTransitionTo(VehicleStatus::RESERVED)
        );

        $this->assertTrue(
            VehicleStatus::AVAILABLE_FOR_RENT
                ->canTransitionTo(VehicleStatus::RENTED)
        );

        $this->assertTrue(
            VehicleStatus::RESERVED
                ->canTransitionTo(VehicleStatus::SOLD)
        );

        $this->assertTrue(
            VehicleStatus::ORDERED
                ->canTransitionTo(VehicleStatus::AVAILABLE_FOR_SALE)
        );

        $this->assertTrue(
            VehicleStatus::MAINTENANCE
                ->canTransitionTo(VehicleStatus::AVAILABLE_FOR_RENT)
        );

        $this->assertFalse(
            VehicleStatus::SOLD
                ->canTransitionTo(VehicleStatus::AVAILABLE_FOR_SALE)
        );
    }
}
