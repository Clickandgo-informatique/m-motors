<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactUsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email :',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner votre email.',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer un email valide.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Renseignez votre email ici',
                ],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Demande :',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre message.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Renseignez votre demande ici.',
                    'rows' => 8,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
