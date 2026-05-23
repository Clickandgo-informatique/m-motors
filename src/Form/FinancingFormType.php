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
            // type de financement (cash, credit, leasing)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Cash' => 'cash',
                    'Crédit' => 'credit',
                    'Leasing' => 'leasing',
                ],
                'placeholder' => 'Choisir un type',
                'required' => true,
            ])

            // statut du financement
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'pending',
                    'Accepté' => 'approved',
                    'Refusé' => 'rejected',
                ],
                'required' => true,
            ])

            // montant financé
            ->add('amount', MoneyType::class, [
                'currency' => 'EUR',
                'required' => false,
            ])

            // durée en mois
            ->add('durationMonths', NumberType::class, [
                'required' => false,
            ])

            // mensualité calculée
            ->add('monthlyPayment', MoneyType::class, [
                'currency' => 'EUR',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Financing::class,
        ]);
    }
}
