<?php

namespace App\Form;

use App\Entity\Feature;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleFeaturesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = (bool) $options['is_admin'];

        if ($isAdmin) {
            $builder->add('features', EntityType::class, [
                'class' => Feature::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'is_admin' => false,
        ]);

        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
