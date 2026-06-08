<?php

namespace App\Tests\Unit\Form;

use App\Entity\VehicleBadge;
use App\Enum\VehicleBadgeCategory;
use App\Form\VehicleBadgeType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class VehicleBadgeTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new VehicleBadgeType(),
                ],
                []
            ),
        ];
    }

    public function testFormUsesVehicleBadgeDataClass(): void
    {
        // Vérifie que le formulaire est associé à l'entité VehicleBadge

        $form = $this->factory->create(VehicleBadgeType::class);

        $this->assertSame(
            VehicleBadge::class,
            $form->getConfig()->getOption('data_class')
        );
    }

    public function testContainsAllExpectedFields(): void
    {
        // Vérifie la présence de tous les champs

        $form = $this->factory->create(VehicleBadgeType::class);

        $this->assertTrue($form->has('code'));
        $this->assertTrue($form->has('label'));
        $this->assertTrue($form->has('category'));
        $this->assertTrue($form->has('priority'));
        $this->assertTrue($form->has('color'));
        $this->assertTrue($form->has('icon'));
        $this->assertTrue($form->has('isActive'));
    }

    public function testCategoryChoicesConfiguration(): void
    {
        // Vérifie les valeurs de l'énumération proposées

        $form = $this->factory->create(VehicleBadgeType::class);

        $choices = $form
            ->get('category')
            ->getConfig()
            ->getOption('choices');

        $this->assertSame(
            [
                'État' => VehicleBadgeCategory::STATE,
                'Commercial' => VehicleBadgeCategory::COMMERCIAL,
                'Écologie' => VehicleBadgeCategory::ECOLOGY,
                'Confiance' => VehicleBadgeCategory::TRUST,
                'Audience' => VehicleBadgeCategory::AUDIENCE,
            ],
            $choices
        );
    }

    public function testPriorityFieldIsOptional(): void
    {
        // Vérifie que la priorité est facultative

        $form = $this->factory->create(VehicleBadgeType::class);

        $this->assertFalse(
            $form->get('priority')
                ->getConfig()
                ->getOption('required')
        );
    }

    public function testColorFieldIsOptional(): void
    {
        // Vérifie que la couleur est facultative

        $form = $this->factory->create(VehicleBadgeType::class);

        $this->assertFalse(
            $form->get('color')
                ->getConfig()
                ->getOption('required')
        );
    }

    public function testIconFieldIsOptional(): void
    {
        // Vérifie que l'icône est facultative

        $form = $this->factory->create(VehicleBadgeType::class);

        $this->assertFalse(
            $form->get('icon')
                ->getConfig()
                ->getOption('required')
        );
    }
}
