<?php

namespace App\Form;

use App\Entity\Brand;
use App\Entity\FuelType;
use App\Entity\Gear;
use App\Entity\Model;
use App\Entity\Variant;
use App\Entity\VehicleModel;
use DateTime;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleModelType extends AbstractType
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
            ->add('homologationDate', DateTimeType::class, ['label'=>'Date homologation', 'attr' => ['class' => 'text-right']])
            ->add('brand', EntityType::class, [
                'label' => 'Marque',
                'class' => Brand::class,
                'choice_label' => 'name',
            ])
            ->add('model', EntityType::class, [
                'label' => 'Modèle',
                'class' => Model::class,
                'choice_label' => 'name',
            ])
            ->add('variant', EntityType::class, [
                'label' => 'Variante',
                'class' => Variant::class,
                'choice_label' => 'name',
            ])
            ->add('fuelType', EntityType::class, [
                'label' => 'Type énergie',
                'class' => FuelType::class,
                'choice_label' => 'name',
            ])
            ->add('gear', EntityType::class, [
                'label' => 'Boîte vitesse',
                'class' => Gear::class,
                'choice_label' => 'type',
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
