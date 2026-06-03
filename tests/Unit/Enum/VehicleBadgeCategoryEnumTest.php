<?php

namespace App\Tests\Unit\Enum;

use App\Enum\VehicleBadgeCategory;
use PHPUnit\Framework\TestCase;

class VehicleBadgeCategoryEnumTest extends TestCase
{
    public function testLabels(): void
    {
        // vérifie les libellés de toutes les catégories

        $this->assertSame(
            'État',
            VehicleBadgeCategory::STATE->label()
        );

        $this->assertSame(
            'Commercial',
            VehicleBadgeCategory::COMMERCIAL->label()
        );

        $this->assertSame(
            'Écologie',
            VehicleBadgeCategory::ECOLOGY->label()
        );

        $this->assertSame(
            'Confiance',
            VehicleBadgeCategory::TRUST->label()
        );

        $this->assertSame(
            'Audience',
            VehicleBadgeCategory::AUDIENCE->label()
        );
    }

    public function testChoices(): void
    {
        // vérifie les choix utilisés dans les formulaires

        $this->assertSame(
            [
                'État' => VehicleBadgeCategory::STATE,
                'Commercial' => VehicleBadgeCategory::COMMERCIAL,
                'Écologie' => VehicleBadgeCategory::ECOLOGY,
                'Confiance' => VehicleBadgeCategory::TRUST,
                'Audience' => VehicleBadgeCategory::AUDIENCE,
            ],
            VehicleBadgeCategory::choices()
        );
    }

    public function testAllCasesHaveLabel(): void
    {
        // vérifie que chaque catégorie possède un libellé

        foreach (VehicleBadgeCategory::cases() as $category) {
            $this->assertNotSame(
                '',
                $category->label()
            );
        }
    }

    public function testChoicesContainsAllCases(): void
    {
        // vérifie qu'aucune catégorie n'est oubliée

        $choices = VehicleBadgeCategory::choices();

        $this->assertCount(
            count(VehicleBadgeCategory::cases()),
            $choices
        );

        foreach (VehicleBadgeCategory::cases() as $category) {
            $this->assertContains(
                $category,
                $choices
            );
        }
    }

    public function testEnumValues(): void
    {
        // vérifie les valeurs stockées en base

        $this->assertSame(
            'state',
            VehicleBadgeCategory::STATE->value
        );

        $this->assertSame(
            'commercial',
            VehicleBadgeCategory::COMMERCIAL->value
        );

        $this->assertSame(
            'ecology',
            VehicleBadgeCategory::ECOLOGY->value
        );

        $this->assertSame(
            'trust',
            VehicleBadgeCategory::TRUST->value
        );

        $this->assertSame(
            'audience',
            VehicleBadgeCategory::AUDIENCE->value
        );
    }
}
