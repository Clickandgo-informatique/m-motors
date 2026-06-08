<?php

namespace App\Tests\Unit\Form;

use App\Entity\Vehicle;
use App\Form\VehicleFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PHPUnit\Framework\TestCase;

class VehicleFormTypeTest extends TestCase
{
    public function testConfigureOptionsSetsVehicleDataClass(): void
    {
        // Vérifie que le formulaire est associé à l'entité Vehicle

        $resolver = new OptionsResolver();

        $formType = new VehicleFormType();

        $formType->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertSame(
            Vehicle::class,
            $options['data_class']
        );
    }

    public function testBuildFormAddsAllExpectedFields(): void
    {
        // Vérifie que tous les champs attendus sont ajoutés

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

        $formType = new VehicleFormType();

        $formType->buildForm($builder, []);

        $this->assertArrayHasKey('status', $fields);
        $this->assertArrayHasKey('vehicleModel', $fields);
        $this->assertArrayHasKey('vehicleModelSearch', $fields);
        $this->assertArrayHasKey('vin', $fields);
        $this->assertArrayHasKey('firstRegistrationDate', $fields);
        $this->assertArrayHasKey('registrationNumber', $fields);
        $this->assertArrayHasKey('mileage', $fields);
        $this->assertArrayHasKey('price', $fields);
        $this->assertArrayHasKey('fuelType', $fields);
        $this->assertArrayHasKey('gearType', $fields);
        $this->assertArrayHasKey('color', $fields);
        $this->assertArrayHasKey('supplier', $fields);
    }

    public function testVehicleModelSearchConfiguration(): void
    {
        // Vérifie la configuration du champ autocomplete

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

        $formType = new VehicleFormType();

        $formType->buildForm($builder, []);

        $options = $fields['vehicleModelSearch'];

        $this->assertFalse($options['mapped']);
        $this->assertTrue($options['required']);

        $this->assertSame(
            'Rechercher un modèle',
            $options['attr']['placeholder']
        );

        $this->assertSame(
            'true',
            $options['attr']['data-autocomplete']
        );
    }

    public function testPriceConfiguration(): void
    {
        // Vérifie la configuration du prix

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

        $formType = new VehicleFormType();

        $formType->buildForm($builder, []);

        $this->assertFalse(
            $fields['price']['currency']
        );
    }

    public function testMileageConfiguration(): void
    {
        // Vérifie la configuration du kilométrage

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

        $formType = new VehicleFormType();

        $formType->buildForm($builder, []);

        $this->assertSame(
            'text-right',
            $fields['mileage']['attr']['class']
        );
    }

    public function testVehicleModelFieldHasNoLabel(): void
    {
        // Vérifie que le champ caché n'affiche pas de label

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

        $formType = new VehicleFormType();

        $formType->buildForm($builder, []);

        $this->assertFalse(
            $fields['vehicleModel']['label']
        );
    }
}
