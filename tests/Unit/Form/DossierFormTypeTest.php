<?php

namespace App\Tests\Unit\Form;

use App\Entity\Dossier;
use App\Enum\DossierType as DossierTypeEnum;
use App\Form\DossierFormType;
use App\Form\FinancingFormType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class DossierFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new DossierFormType(),
                    new FinancingFormType(),
                ],
                []
            ),
        ];
    }

    public function testFormUsesDossierDataClass(): void
    {
        // Vérifie l'entité associée au formulaire

        $form = $this->factory->create(DossierFormType::class);

        $this->assertSame(
            Dossier::class,
            $form->getConfig()->getOption('data_class')
        );
    }

    public function testDefaultIsAdminOptionIsFalse(): void
    {
        // Vérifie la valeur par défaut de l'option is_admin

        $form = $this->factory->create(DossierFormType::class);

        $this->assertFalse(
            $form->getConfig()->getOption('is_admin')
        );
    }

    public function testContainsTypeField(): void
    {
        // Vérifie la présence du champ type

        $form = $this->factory->create(DossierFormType::class);

        $this->assertTrue($form->has('type'));
    }

    public function testContainsFinancingField(): void
    {
        // Vérifie la présence du formulaire embarqué financement

        $form = $this->factory->create(DossierFormType::class);

        $this->assertTrue($form->has('financing'));
    }

    public function testDoesNotContainAdminFieldsByDefault(): void
    {
        // Vérifie que les champs réservés à l'administration ne sont pas affichés

        $form = $this->factory->create(DossierFormType::class);

        $this->assertFalse($form->has('customer'));
        $this->assertFalse($form->has('vehicle'));
    }

    public function testContainsAdminFieldsWhenIsAdminIsTrue(): void
    {
        // Vérifie que les champs d'administration sont ajoutés

        $form = $this->factory->create(
            DossierFormType::class,
            null,
            [
                'is_admin' => true,
            ]
        );

        $this->assertTrue($form->has('customer'));
        $this->assertTrue($form->has('vehicle'));
    }

    public function testTypeFieldIsRequired(): void
    {
        // Vérifie que le type de dossier est obligatoire

        $form = $this->factory->create(DossierFormType::class);

        $this->assertTrue(
            $form->get('type')
                ->getConfig()
                ->getOption('required')
        );
    }

    public function testTypeFieldUsesAllEnumCases(): void
    {
        // Vérifie que toutes les valeurs de l'énumération sont proposées

        $form = $this->factory->create(DossierFormType::class);

        $choices = $form->get('type')
            ->getConfig()
            ->getOption('choices');

        $this->assertSame(
            DossierTypeEnum::cases(),
            $choices
        );
    }

    public function testFinancingFieldConfiguration(): void
    {
        // Vérifie la configuration du formulaire embarqué

        $form = $this->factory->create(DossierFormType::class);

        $field = $form->get('financing');

        $this->assertFalse(
            $field->getConfig()->getOption('required')
        );

        $this->assertFalse(
            $field->getConfig()->getOption('label')
        );
    }
}
