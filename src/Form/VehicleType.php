<?php

namespace App\Form;

use App\Entity\BodyType;
use App\Entity\Brand;
use App\Entity\Color;
use App\Entity\Feature;
use App\Entity\FuelType;
use App\Entity\Gear;
use App\Entity\Model;
use App\Entity\Supplier;
use App\Entity\Variant;
use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('year')
            ->add('mileage')
            ->add('price')
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'choice_label' => 'id',
            ])
            ->add('gear', EntityType::class, [
                'class' => Gear::class,
                'choice_label' => 'id',
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'id',
            ])
            ->add('fuel_type', EntityType::class, [
                'class' => FuelType::class,
                'choice_label' => 'id',
            ])
            ->add('body_type', EntityType::class, [
                'class' => BodyType::class,
                'choice_label' => 'id',
            ])
            ->add('color', EntityType::class, [
                'class' => Color::class,
                'choice_label' => 'id',
            ])
            ->add('feature', EntityType::class, [
                'class' => Feature::class,
                'choice_label' => 'id',
            ])
            ->add('model', EntityType::class, [
                'class' => Model::class,
                'choice_label' => 'id',
            ])
            ->add('variant', EntityType::class, [
                'class' => Variant::class,
                'choice_label' => 'id',
            ])
            ->add('vehicleModel', EntityType::class, [
                'class' => VehicleModel::class,
                'choice_label' => 'id',
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
