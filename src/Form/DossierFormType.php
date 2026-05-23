<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Dossier;
use App\Entity\Vehicle;
use App\Enum\DossierType;
use App\Enum\FinancingType;
use App\Form\FinancingFormType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'];

        if ($isAdmin) {
            $builder
                ->add('customer', EntityType::class, [
                    'class' => Customer::class,
                    'choice_label' => fn(Customer $c) => $c->getFirstName() . ' ' . $c->getLastName(),
                    'placeholder' => 'choisir un client',
                    'required' => true,
                ])
                ->add('vehicle', EntityType::class, [
                    'class' => Vehicle::class,
                    'choice_label' => fn(Vehicle $v) =>
                    $v->getVehicleModel(),
                    'placeholder' => 'choisir un véhicule',
                    'required' => true,
                ]);
        }

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'type de dossier',
                'choices' => DossierType::cases(),
                'choice_label' => fn(DossierType $t) => $t->value,
            ])

            ->add('financingType', ChoiceType::class, [
                'label' => 'mode de financement',
                'choices' => FinancingType::cases(),
                'choice_label' => fn(FinancingType $t) => $t->value,
                'required' => false,
                'placeholder' => 'choisir',
            ]);

        // embedding financing entity
        $builder->add('financing', FinancingFormType::class, [
            'required' => false,
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'is_admin' => false,
        ]);
    }
}
