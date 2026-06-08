<?php

namespace App\Tests\Unit\Form;

use App\Entity\VehicleModel;
use App\Form\VehicleModelFormType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleModelFormTypeTest extends TestCase
{
    public function testConfigureOptionsSetsVehicleModelDataClass(): void
    {
        // Vérifie que le formulaire est associé à l'entité VehicleModel

        $resolver = new OptionsResolver();

        $formType = new VehicleModelFormType();

        $formType->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertSame(
            VehicleModel::class,
            $options['data_class']
        );
    }

    public function testBuildFormAddsAllExpectedFields(): void
    {
        // Vérifie que tous les champs sont ajoutés

        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    ?string $type = null,
                    array $options = []
                ) use (&$fields, $builder) {
                    $fields[$name] = [
                        'type' => $type,
                        'options' => $options,
                    ];

                    return $builder;
                }
            );

        $formType = new VehicleModelFormType();

        $formType->buildForm($builder, []);

        $expectedFields = [
            'powerHp',
            'powerFiscal',
            'co2',
            'consumption',
            'massMin',
            'massMax',
            'cnit',
            'utacCode',
            'euroNorm',
            'homologationDate',
            'brand',
            'model',
            'variant',
            'fuelType',
            'gearType',
            'bodyType',
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $fields);
        }
    }

    public function testPowerFiscalConfiguration(): void
    {
        // Vérifie la configuration de la puissance fiscale

        $fields = $this->buildFields();

        $this->assertSame(
            'Puissance fiscale',
            $fields['powerFiscal']['label']
        );

        $this->assertSame(
            1,
            $fields['powerFiscal']['scale']
        );
    }

    public function testConsumptionConfiguration(): void
    {
        // Vérifie la configuration de la consommation

        $fields = $this->buildFields();

        $this->assertSame(
            'Consommation l/100km',
            $fields['consumption']['label']
        );

        $this->assertSame(
            1,
            $fields['consumption']['scale']
        );
    }

    public function testMassFieldsConfiguration(): void
    {
        // Vérifie la configuration des poids min et max

        $fields = $this->buildFields();

        $this->assertTrue(
            $fields['massMin']['grouping']
        );

        $this->assertTrue(
            $fields['massMax']['grouping']
        );
    }

    public function testBrandFieldConfiguration(): void
    {
        // Vérifie la configuration du champ marque

        $fields = $this->buildFields();

        $this->assertSame(
            'Choisir une marque',
            $fields['brand']['placeholder']
        );

        $this->assertFalse(
            $fields['brand']['required']
        );
    }

    public function testBodyTypeFieldConfiguration(): void
    {
        // Vérifie la configuration du type de carrosserie

        $fields = $this->buildFields();

        $this->assertSame(
            'Choisir un type',
            $fields['bodyType']['placeholder']
        );

        $this->assertFalse(
            $fields['bodyType']['required']
        );
    }

    public function testModelFieldConfiguration(): void
    {
        // Vérifie la configuration du modèle

        $fields = $this->buildFields();

        $this->assertSame(
            'Description du modèle',
            $fields['model']['placeholder']
        );
    }

    public function testVariantFieldConfiguration(): void
    {
        // Vérifie la configuration de la variante

        $fields = $this->buildFields();

        $this->assertSame(
            'Choisir une variante',
            $fields['variant']['placeholder']
        );
    }

    public function testFuelTypeFieldConfiguration(): void
    {
        // Vérifie la configuration du type d'énergie

        $fields = $this->buildFields();

        $this->assertSame(
            'Choisir type d\'énergie',
            $fields['fuelType']['placeholder']
        );
    }

    public function testGearTypeFieldConfiguration(): void
    {
        // Vérifie la configuration du type de transmission

        $fields = $this->buildFields();

        $this->assertSame(
            'Choisir le type de transmission',
            $fields['gearType']['placeholder']
        );
    }

    private function buildFields(): array
    {
        $fields = [];

        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->method('add')
            ->willReturnCallback(
                function (
                    string $name,
                    ?string $type = null,
                    array $options = []
                ) use (&$fields, $builder) {
                    $fields[$name] = $options;

                    return $builder;
                }
            );

        $formType = new VehicleModelFormType();

        $formType->buildForm($builder, []);

        return $fields;
    }
}
