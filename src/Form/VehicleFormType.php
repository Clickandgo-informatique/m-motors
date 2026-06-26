<?php

namespace App\Form;

use App\Entity\Color;
use App\Entity\FuelType;
use App\Entity\GearType;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
use App\Enum\VehicleUsageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // Statut véhicule
            ->add('status', EnumType::class, [
                'class' => VehicleStatus::class,
                'label' => 'Statut',
                'choice_label' => fn(VehicleStatus $status) => $status->label(),
                'choice_value' => fn(?VehicleStatus $status) => $status?->value,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            // Usage véhicule (enum stable + sans logique Twig)
            ->add('usageType', EnumType::class, [
                'class' => VehicleUsageType::class,
                'label' => 'Mode de vente',

                'expanded' => true,
                'multiple' => false,

                'choice_label' => fn(VehicleUsageType $type) => sprintf(
                    '<i class="%s"></i> %s',
                    $type->icon(),
                    $type->label()
                ),

                'choice_value' => fn(?VehicleUsageType $type) => $type?->value,
                'label_html' => true,
            ])

            // ID modèle véhicule
            ->add('vehicleModel', HiddenType::class, [
                'label' => false,
            ])

            // Autocomplete modèle
            ->add('vehicleModelSearch', TextType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'Modèle du véhicule',
                'attr' => [
                    'placeholder' => 'Rechercher un modèle',
                    'data-autocomplete' => 'true',
                    'data-url' => '/vehicle-model/search',
                    'data-target' => 'vehicle-model-results',
                    'data-result-links' => 'true',
                    'data-item-url' => '/vehicles/models/ID_PLACEHOLDER/edit'
                ],
            ])

            ->add('vin', TextType::class, [
                'label' => 'VIN'
            ])

            ->add('firstRegistrationDate', DateTimeType::class, [
                'label' => 'Date 1ère immatriculation',
            ])

            ->add('registrationNumber', TextType::class, [
                'label' => 'Immatriculation'
            ])

            ->add('mileage', IntegerType::class, [
                'label' => 'Kilométrage',
            ])

            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => false,
            ])

            ->add('fuelType', EntityType::class, [
                'class' => FuelType::class,
                'choice_label' => 'name',
                'label' => 'Carburant'
            ])

            ->add('gearType', EntityType::class, [
                'class' => GearType::class,
                'choice_label' => 'name',
                'label' => 'Boîte'
            ])

            ->add('color', EntityType::class, [
                'class' => Color::class,
                'choice_label' => 'name',
                'label' => 'Couleur'
            ])

            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'label' => 'Fournisseur'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}
