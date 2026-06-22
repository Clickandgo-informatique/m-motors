<?php

namespace App\Form;

use App\Entity\Feature;
use App\Entity\FeatureCategory;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class FeatureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isAdmin = $options['is_admin'];


        $builder
            ->add('label', TextType::class, [
                'label' => 'Intitulé',
                'attr' => ['placeholder' => 'Intitulé de l\'option']
            ])
            ->add('category', EntityType::class, [
                'class' => FeatureCategory::class,
                'choice_label' => fn(FeatureCategory $fc) => $fc->getLabel(),
                'placeholder' => 'Choisir une catégorie',
                'required' => true,
                'label' => 'Choisir une catégorie d\'option'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feature::class,
            'is_admin' => false,
        ]);
    }
}
