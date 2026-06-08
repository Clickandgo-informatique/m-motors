<?php

namespace App\Tests\Unit\Form;

use App\Entity\Dossier;
use App\Enum\DossierType as DossierTypeEnum;
use App\Form\DossierFormType;
use App\Form\FinancingFormType;
use Symfony\Component\Form\FormBuilderInterface;
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
        // Vérifie que le formulaire est associé à l'entité Dossier

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
        // Vérifie la présence du formulaire embarqué financing

        $form = $this->factory->create(DossierFormType::class);

        $this->assertTrue($form->has('financing'));
    }

    public function testDoesNotContainAdminFieldsByDefault(): void
    {
        // Vérifie que les champs administrateur ne sont pas présents
        // lorsque l'option is_admin vaut false

        $form = $this->factory->create(DossierFormType::class);

        $this->assertFalse($form->has('customer'));
        $this->assertFalse($form->has('vehicle'));
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

    public function testTypeFieldUsesEnumCases(): void
    {
        // Vérifie que toutes les valeurs de l'énumération sont utilisées

        $form = $this->factory->create(DossierFormType::class);

        $choices = $form
            ->get('type')
            ->getConfig()
            ->getOption('choices');

        $this->assertSame(
            DossierTypeEnum::cases(),
            $choices
        );
    }

    public function testFinancingFieldIsOptional(): void
    {
        // Vérifie que le formulaire de financement est facultatif

        $form = $this->factory->create(DossierFormType::class);

        $this->assertFalse(
            $form->get('financing')
                ->getConfig()
                ->getOption('required')
        );
    }

    public function testFinancingFieldHasNoLabel(): void
    {
        // Vérifie que le label du formulaire embarqué est désactivé

        $form = $this->factory->create(DossierFormType::class);

        $this->assertFalse(
            $form->get('financing')
                ->getConfig()
                ->getOption('label')
        );
    }

    public function testBuildFormAddsAdminFieldsWhenIsAdminIsTrue(): void
    {
        // Vérifie que les champs réservés à l'administration sont ajoutés

        $calls = [];

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    ?string $type = null,
                    array $options = []
                ) use (&$calls, $builder) {
                    $calls[] = [
                        'name' => $name,
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                }
            );

        $formType = new DossierFormType();

        $formType->buildForm(
            $builder,
            [
                'is_admin' => true,
            ]
        );

        $fieldNames = array_column($calls, 'name');

        $this->assertContains('customer', $fieldNames);
        $this->assertContains('vehicle', $fieldNames);
        $this->assertContains('type', $fieldNames);
        $this->assertContains('financing', $fieldNames);
    }

    public function testBuildFormDoesNotAddAdminFieldsWhenIsAdminIsFalse(): void
    {
        // Vérifie que les champs administrateur ne sont pas ajoutés

        $calls = [];

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    ?string $type = null,
                    array $options = []
                ) use (&$calls, $builder) {
                    $calls[] = $name;

                    return $builder;
                }
            );

        $formType = new DossierFormType();

        $formType->buildForm(
            $builder,
            [
                'is_admin' => false,
            ]
        );

        $this->assertContains('type', $calls);
        $this->assertContains('financing', $calls);

        $this->assertNotContains('customer', $calls);
        $this->assertNotContains('vehicle', $calls);
    }
}
