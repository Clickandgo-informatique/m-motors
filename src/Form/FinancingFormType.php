<?php

namespace App\Form;

use App\Entity\Financing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class FinancingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Comptant' => 'cash',
                    'Crédit' => 'credit',
                    'Leasing' => 'leasing',
                ],
                'placeholder' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'le type de financement est obligatoire'
                    ])
                ],
            ])

            ->add('leasingType', ChoiceType::class, [
                'choices' => [
                    'LOA' => 'loa',
                    'LLD' => 'lld',
                ],
                'label' => 'Type de leasing',
                'placeholder' => 'Choisissez un type de leasing',
                'required' => false,
            ])

            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'pending',
                    'Approuvé' => 'approved',
                    'Refusé' => 'rejected',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'le statut est obligatoire'
                    ])
                ],
                'label' => 'Statut dossier'
            ])

            ->add('amount', MoneyType::class, [
                'currency' => 'EUR',
                'required' => false,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'le montant doit être positif'
                    ])
                ],
                'label' => 'Montant'
            ])

            ->add('durationMonths', NumberType::class, [
                'required' => false,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'la durée doit être supérieure à 0'
                    ])
                ],
                'label' => 'Durée en mois'
            ])

            ->add('monthlyPayment', MoneyType::class, [
                'currency' => 'EUR',
                'required' => false,
                'label' => 'Mensualité (€)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Financing::class,
        ]);
    }
}
