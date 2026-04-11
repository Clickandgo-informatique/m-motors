<?php

namespace App\Form;

use App\Entity\Dossier;
use App\Enum\DossierType;
use App\Enum\FinancingType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ========================= CHAMPS DE BASE =========================

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de dossier',
                'choices' => DossierType::choices(),
                'choice_label' => fn($choice) => $choice->label(),
                'placeholder' => 'Choisir un type',
            ])

            ->add('financingType', ChoiceType::class, [
                'label' => 'Mode de financement',
                'choices' => FinancingType::choices(),
                'choice_label' => fn($choice) => $choice->label(),
                'placeholder' => 'Choisir un financement',
                'required' => false,
            ]);

        // ========================= FORM DYNAMIQUE =========================

        /**
         * Ajout dynamique des champs LLD / LOA
         */
        $formModifier = function ($form, ?FinancingType $financingType) {

            // On nettoie d'abord (important en dynamique)
            foreach (['duration', 'annualMileage', 'monthlyPayment'] as $field) {
                if ($form->has($field)) {
                    $form->remove($field);
                }
            }

            if (!$financingType) {
                return;
            }

            // Champs spécifiques leasing
            if (in_array($financingType, [FinancingType::LOA, FinancingType::LLD], true)) {

                $form
                    ->add('duration', IntegerType::class, [
                        'label' => 'Durée (mois)',
                        'required' => true,
                    ])
                    ->add('annualMileage', IntegerType::class, [
                        'label' => 'Kilométrage annuel',
                        'required' => false,
                    ])
                    ->add('monthlyPayment', MoneyType::class, [
                        'label' => 'Mensualité estimée',
                        'currency' => 'EUR',
                        'required' => true,
                    ]);
            }
        };

        // ========================= EVENTS =========================

        // Initialisation (édition / reload)
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($formModifier) {
            /** @var Dossier|null $data */
            $data = $event->getData();
            $formModifier($event->getForm(), $data?->getFinancingType());
        });

        // Soumission (changement du select)
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier) {
            $data = $event->getData();

            $financingType = isset($data['financingType'])
                ? FinancingType::tryFrom($data['financingType'])
                : null;

            $formModifier($event->getForm(), $financingType);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
        ]);
    }
}
