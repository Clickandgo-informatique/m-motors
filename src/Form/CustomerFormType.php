<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('customerCode', TextType::class, [
                'label' => 'Code client',
                'required' => false,
                'disabled' => true,
                'attr' => [
                    'readonly' => true
                ]
            ])

            ->add('zipcode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'text-right'
                ]
            ])
            ->add('city', TextType::class, ['label' => 'Ville'])
            ->add('address', TextType::class, ['label' => 'Adresse'])
            ->add('addressDetails', TextareaType::class, ['label' => 'Détails adresse'])
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Créé le',
                'disabled' => true
            ])
            ->add('updatedAt', DateTimeType::class, [
                'label' => 'Modifié le',
                'disabled' => true
            ])
            ->add('phoneNumber1', TextType::class, [
                'label' => 'Téléphone principal',
                'required' => false,
                'attr' => [
                    'placeholder' => '06 12 34 56 78 ou +33 6 12 34 56 78',
                    'pattern' => '^(\+33|0)[1-9](\d{2}){4}$',
                    'class' => 'text-right'
                ]
            ])
            ->add('phoneNumber2', TextType::class, [
                'label' => 'Téléphone secondaire',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Optionnel',
                    'pattern' => '^(\+33|0)[1-9](\d{2}){4}$',
                    'class' => 'text-right'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
