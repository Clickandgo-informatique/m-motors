<?php

namespace App\Tests\Unit\Enum;

use App\Enum\RentalStatus;
use PHPUnit\Framework\TestCase;

class RentalStatusEnumTest extends TestCase
{
    public function testCreatedLabel(): void
    {
        // vérifie le libellé du statut créé

        $this->assertSame(
            'Créée',
            RentalStatus::CREATED->label()
        );
    }

    public function testConfirmedLabel(): void
    {
        // vérifie le libellé du statut confirmé

        $this->assertSame(
            'Confirmée',
            RentalStatus::CONFIRMED->label()
        );
    }

    public function testActiveLabel(): void
    {
        // vérifie le libellé du statut en cours

        $this->assertSame(
            'En cours',
            RentalStatus::ACTIVE->label()
        );
    }

    public function testFinishedLabel(): void
    {
        // vérifie le libellé du statut terminé

        $this->assertSame(
            'Terminée',
            RentalStatus::FINISHED->label()
        );
    }

    public function testCanceledLabel(): void
    {
        // vérifie le libellé du statut annulé

        $this->assertSame(
            'Annulée',
            RentalStatus::CANCELED->label()
        );
    }

    public function testAllCasesHaveLabel(): void
    {
        // vérifie que chaque statut possède un libellé non vide

        foreach (RentalStatus::cases() as $status) {
            $this->assertNotSame(
                '',
                $status->label()
            );
        }
    }

    public function testEnumValues(): void
    {
        // vérifie les valeurs internes de l'enum

        $this->assertSame(
            'created',
            RentalStatus::CREATED->value
        );

        $this->assertSame(
            'confirmed',
            RentalStatus::CONFIRMED->value
        );

        $this->assertSame(
            'active',
            RentalStatus::ACTIVE->value
        );

        $this->assertSame(
            'finished',
            RentalStatus::FINISHED->value
        );

        $this->assertSame(
            'canceled',
            RentalStatus::CANCELED->value
        );
    }
}