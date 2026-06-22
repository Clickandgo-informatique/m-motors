<?php

namespace App\Form;

use App\Entity\BodyType;
use App\Entity\Brand;
use App\Entity\Feature;
use App\Entity\FuelType;
use App\Entity\GearType;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\VehicleModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleModelFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('powerHp', IntegerType::class, ['label' => 'Cv DIN', 'attr' => ['class' => 'text-right']])
            ->add('powerFiscal', NumberType::class, ['scale' => 2, 'label' => 'Puissance fiscale', 'scale' => 1, 'attr' => ['class' => 'text-right']])
            ->add('co2', IntegerType::class, ['label' => 'Emissions CO2', 'attr' => ['class' => 'text-right']])
            ->add('consumption', NumberType::class, ['scale' => 1, 'label' => 'Consommation l/100km', 'attr' => ['class' => 'text-right']])
            ->add('massMin', NumberType::class, ['label' => 'Poids min.', 'grouping' => true, 'attr' => ['class' => 'text-right']])
            ->add('massMax', NumberType::class, ['label' => 'Poids max.', 'grouping' => true, 'attr' => ['class' => 'text-right']])
            ->add('cnit')
            ->add('utacCode')
            ->add('euroNorm')
            ->add('homologationDate', DateTimeType::class, ['label' => 'Date homologation', 'attr' => ['class' => 'text-right']])
            ->add('brand', EntityType::class, [
                'label' => 'Marque',
                'class' => Brand::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une marque',
                'required' => false
            ])
            ->add('model', EntityType::class, [
                'label' => 'Modèle',
                'class' => Model::class,
                'choice_label' => 'name',
                'placeholder' => 'Description du modèle'
            ])
            ->add('variant', EntityType::class, [
                'label' => 'Variante',
                'class' => Variant::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une variante'

            ])
            ->add('fuelType', EntityType::class, [
                'label' => 'Type énergie',
                'class' => FuelType::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir type d\'énergie'
            ])
            ->add('gearType', EntityType::class, [
                'label' => 'Boîte vitesse',
                'class' => GearType::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir le type de transmission'
            ])
            ->add('bodyType', EntityType::class, [
                'label' => 'Type carrosserie',
                'class' => BodyType::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un type',
                'required' => false
            ])
            ->add('features', EntityType::class, [
                'class' => Feature::class,
                'multiple' => true,
                'required' => false,
                'choice_label' => 'label',
                'attr' => ['hidden' => true]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VehicleModel::class,
        ]);
    }
}
