<?php

namespace App\Form;

use App\Entity\VehicleBadge;
use App\Enum\VehicleBadgeCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleBadgeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class)
            ->add('label', TextType::class)
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'État' => VehicleBadgeCategory::STATE,
                    'Commercial' => VehicleBadgeCategory::COMMERCIAL,
                    'Écologie' => VehicleBadgeCategory::ECOLOGY,
                    'Confiance' => VehicleBadgeCategory::TRUST,
                    'Audience' => VehicleBadgeCategory::AUDIENCE,
                ],
            ])
            ->add('priority', IntegerType::class, [
                'required' => false,
            ])
            ->add('color', ColorType::class, [
                'required' => false,
            ])
            ->add('icon', TextType::class, [
                'required' => false,
            ])
            ->add('isActive');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VehicleBadge::class,
        ]);
    }
}