<?php

namespace App\Form;

use App\Entity\User;
use App\Validator\PasswordStrength;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email') // Validation sur l'entité User (Unique, Email, NotBlank)
            ->add('accept2fa', CheckboxType::class, [
                'mapped' => false, // pas de propriété User correspondante
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter l’activation du 2FA pour sécuriser votre compte.',
                    ]),
                ],
                'label' => 'J’accepte d’activer le 2FA sur mon compte',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false, // pas de propriété User correspondante
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d’utilisation.',
                    ]),
                ],
                'label' => "J'accepte les conditions d'utilisation"
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label'=>'Mot de passe',
                'attr' => ['id' => 'password'], // pour JS
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un mot de passe']),
                    new PasswordStrength(['minPercent' => 60]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
