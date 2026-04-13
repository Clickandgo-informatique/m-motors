<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\FinancingType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
        $isAdmin = $options['is_admin'];

        // =========================
        // ADMIN ONLY FIELDS
        // =========================
        if ($isAdmin) {
            $builder
                ->add('customer', EntityType::class, [
                    'class' => Customer::class,
                    'choice_label' => fn($c) => $c->getFirstname() . ' ' . $c->getLastname(),
                    'label' => 'Client',
                    'placeholder' => 'Choisir un client',
                ])
                ->add('vehicle', EntityType::class, [
                    'class' => Vehicle::class,
                    'choice_label' => fn($v) => $v->getBrand() . ' ' . $v->getModel(),
                    'label' => 'Véhicule',
                    'placeholder' => 'Choisir un véhicule',
                ]);
        }

        // =========================
        // COMMON FIELD
        // =========================
        $builder->add('financingType', ChoiceType::class, [
            'label' => 'Mode d’acquisition',
            'choices' => FinancingType::choices(),
            'choice_label' => fn($choice) => $choice->label(),
            'placeholder' => 'Choisir une option',
            'required' => false,
        ]);

        // =========================
        // DYNAMIC FIELDS
        // =========================
        $formModifier = function ($form, ?FinancingType $type) {

            foreach (['duration', 'annualMileage', 'monthlyPayment'] as $field) {
                if ($form->has($field)) {
                    $form->remove($field);
                }
            }

            if (!$type) {
                return;
            }

            if (in_array($type, [FinancingType::LOA, FinancingType::LLD], true)) {
                $form
                    ->add('duration', IntegerType::class, [
                        'label' => 'Durée (mois)',
                    ])
                    ->add('annualMileage', IntegerType::class, [
                        'label' => 'Kilométrage annuel',
                    ])
                    ->add('monthlyPayment', MoneyType::class, [
                        'label' => 'Mensualité',
                        'currency' => 'EUR',
                    ]);
            }
        };

        // =========================
        // EVENTS
        // =========================

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($formModifier) {
            $formModifier($event->getForm(), $event->getData()?->getFinancingType());
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier) {
            $data = $event->getData();

            $type = isset($data['financingType'])
                ? FinancingType::tryFrom($data['financingType'])
                : null;

            $formModifier($event->getForm(), $type);
        });

        // =========================
        // BUSINESS LOGIC
        // =========================
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {

            /** @var Dossier $dossier */
            $dossier = $event->getData();

            if (!$dossier->getFinancingType()) {
                return;
            }

            $dossier->setType(
                in_array(
                    $dossier->getFinancingType(),
                    [FinancingType::LOA, FinancingType::LLD],
                    true
                )
                    ? DossierType::FINANCING
                    : DossierType::PURCHASE
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'is_admin' => false,
        ]);
    }
}
