<?php

namespace App\Form;

use App\Entity\Financing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FinancingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /**
             * type principal financement
             */
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Comptant' => 'cash',
                    'Crédit' => 'credit',
                    'Leasing' => 'leasing',
                ],
                'placeholder' => false,
                'empty_data' => 'cash',
                'required' => true,
                'label' => 'Type de financement',
            ])

            /**
             * sous-type leasing (uniquement si type = leasing côté UI)
             */
            ->add('leasingType', ChoiceType::class, [
                'choices' => [
                    'LOA' => 'loa',
                    'LLD' => 'lld',
                ],
                'required' => false,
                'placeholder' => 'Choisir un type de leasing',
                'label' => 'Type de leasing',
            ])

            /**
             * statut financement
             */
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'pending',
                    'Approuvé' => 'approved',
                    'Refusé' => 'rejected',
                ],
                'placeholder' => false,
                'empty_data' => 'pending',
                'required' => true,
                'label' => 'Statut',
            ])

            /**
             * montant financement
             */
            ->add('amount', MoneyType::class, [
                'currency' => 'EUR',
                'required' => false,
                'label' => 'Montant financé',
            ])

            /**
             * durée financement
             */
            ->add('durationMonths', NumberType::class, [
                'required' => false,
                'label' => 'Durée en mois',
            ])

            /**
             * mensualité
             */
            ->add('monthlyPayment', MoneyType::class, [
                'currency' => 'EUR',
                'required' => false,
                'label' => 'Mensualité',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Financing::class,
        ]);
    }
}