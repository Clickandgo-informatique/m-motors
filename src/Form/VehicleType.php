<?php

namespace App\Form;

use App\Entity\Color;
use App\Entity\Feature;
use App\Entity\FuelType;
use App\Entity\Gear;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Enum\VehicleStatus;
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

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            //Statut du véhicule (enum)
            ->add('status', EnumType::class, [
                'class' => VehicleStatus::class,
                'label' => 'Statut',
                'choice_label' => fn(VehicleStatus $status) => $status->label(),
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            // ID réel envoyé à Symfony (champ caché)
            ->add('vehicleModel', HiddenType::class, [
                'label' => false
            ])

            //Champ autocomplete
            ->add('vehicleModelSearch', TextType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'Modèle du véhicule',

                'attr' => [
                    'placeholder' => 'Rechercher un modèle',
                    'data-autocomplete' => 'true',
                    'data-result-div' => '#vehicle-model-results',
                    'data-target-hidden' => '#vehicle_vehicleModel'
                ],
            ])

            ->add('vin', TextType::class, [
                'label' => 'VIN'
            ])
            ->add('firstRegistrationDate', DateTimeType::class, ['label' => 'Date 1ère immatriculation', 'attr' => [
                'class' => 'text-right',
            ],])

            ->add('registrationNumber', TextType::class, [
                'label' => 'Immatriculation'
            ])

            ->add('year', IntegerType::class, [
                'label' => 'Année'
            ])

            ->add('mileage', IntegerType::class, [
                'label' => 'Kilométrage'
            ])

            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'attr' => [
                    'class' => 'text-right',
                ],
            ])

            ->add('fuelType', EntityType::class, [
                'class' => FuelType::class,
                'choice_label' => 'name',
                'label' => 'Carburant'
            ])

            ->add('gear', EntityType::class, [
                'class' => Gear::class,
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

            ->add('features', EntityType::class, [
                'class' => Feature::class,
                'choice_label' => 'name',
                'multiple' => true,
                'label' => 'Options'
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
