<?php

namespace App\Tests\Unit\Enum;

use App\Enum\FinancingType;
use PHPUnit\Framework\TestCase;

class FinancingTypeEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie les libellés affichés en interface

        $this->assertSame(
            'Comptant',
            FinancingType::CASH->label()
        );

        $this->assertSame(
            'Crédit',
            FinancingType::CREDIT->label()
        );

        $this->assertSame(
            'LOA',
            FinancingType::LOA->label()
        );

        $this->assertSame(
            'LLD',
            FinancingType::LLD->label()
        );
    }

    public function testIcons(): void
    {
        // vérifie les icônes associées aux types de financement

        $this->assertSame(
            'fa-solid fa-sack-dollar',
            FinancingType::CASH->icon()
        );

        $this->assertSame(
            'fa-solid fa-landmark',
            FinancingType::CREDIT->icon()
        );

        $this->assertSame(
            'fa-solid fa-car-side',
            FinancingType::LOA->icon()
        );

        $this->assertSame(
            'fa-solid fa-calendar-days',
            FinancingType::LLD->icon()
        );
    }

    public function testChoices(): void
    {
        // vérifie les choix utilisés dans les formulaires

        $this->assertSame(
            [
                'Comptant' => FinancingType::CASH,
                'Crédit' => FinancingType::CREDIT,
                'LOA' => FinancingType::LOA,
                'LLD' => FinancingType::LLD,
            ],
            FinancingType::choices()
        );
    }

    public function testAllCasesHaveLabel(): void
    {
        // vérifie que chaque valeur possède un libellé non vide

        foreach (FinancingType::cases() as $type) {
            $this->assertNotSame(
                '',
                $type->label()
            );
        }
    }

    public function testAllCasesHaveIcon(): void
    {
        // vérifie que chaque valeur possède une icône non vide

        foreach (FinancingType::cases() as $type) {
            $this->assertNotSame(
                '',
                $type->icon()
            );
        }
    }
}
