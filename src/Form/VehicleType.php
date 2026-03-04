<?php

namespace App\Form;

use App\Entity\Color;
use App\Entity\Vehicle;
use App\Entity\VehicleModel;
use App\Repository\VehicleModelRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vin', null, [
                'label' => 'VIN',
                'attr' => [
                    'placeholder' => 'Numéro VIN (17 caractères)',
                ],
            ])

            ->add('registrationNumber', null, [
                'label' => 'Immatriculation',
                'attr' => [
                    'placeholder' => 'AA-123-BB',
                ],
            ])

            ->add('mileage', null, [
                'label' => 'Kilométrage',
                'attr' => [
                    'placeholder' => '150000',
                ],
            ])

            ->add('firstRegistrationDate', DateType::class, [
                'label' => 'Date de première mise en circulation',
                'widget' => 'single_text',
            ])

            ->add('year', null, [
                'label' => 'Année',
                'attr' => [
                    'placeholder' => '2022',
                ],
            ])

            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => '15000',
                ],
            ])

            ->add('status', null, [
                'label' => 'Statut',
            ])

            ->add('color', EntityType::class, [
                'class' => Color::class,
                'choice_label' => 'name',
                'label' => 'Couleur',
                'placeholder' => 'Choisir une couleur',
            ])

            ->add('model', EntityType::class, [
                'class' => VehicleModel::class,
                'choice_label' => function (VehicleModel $vm) {
                    return $vm->getBrand()?->getName() . ' ' . $vm->getModel()?->getName();
                },
                'query_builder' => function (VehicleModelRepository $repo) {
                    return $repo->createQueryBuilder('vm')
                        ->leftJoin('vm.brand', 'b')
                        ->leftJoin('vm.model', 'm')
                        ->addSelect('b', 'm')
                        ->orderBy('b.name', 'ASC');
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}
