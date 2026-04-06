<?php

namespace App\Form;

use App\Entity\Dossier;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => 'model.name', // afficher le modèle du véhicule
                'label' => 'Véhicule',
                'placeholder' => 'Sélectionner un véhicule'
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Achat' => 'purchase',
                    'Location' => 'rental',
                ],
                'label' => 'Type de dossier',
                'placeholder' => 'Choisir un type'
            ]);
        // Le status, customer, etc. seront gérés dans le controller
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
        ]);
    }
}
