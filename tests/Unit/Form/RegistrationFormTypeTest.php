<?php

namespace App\Tests\Unit\Form;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Validator\PasswordStrength;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class RegistrationFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new PreloadedExtension(
                [
                    new RegistrationFormType(),
                ],
                []
            ),
            new ValidatorExtension($validator),
        ];
    }

    public function testFormUsesUserDataClass(): void
    {
        // Vérifie que le formulaire est associé à l'entité User

        $form = $this->factory->create(RegistrationFormType::class);

        $this->assertSame(
            User::class,
            $form->getConfig()->getOption('data_class')
        );
    }

    public function testContainsAllExpectedFields(): void
    {
        // Vérifie la présence des champs

        $form = $this->factory->create(RegistrationFormType::class);

        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('accept2fa'));
        $this->assertTrue($form->has('agreeTerms'));
        $this->assertTrue($form->has('plainPassword'));
    }

    public function testAccept2FaFieldConfiguration(): void
    {
        // Vérifie la configuration du champ accept2fa

        $form = $this->factory->create(RegistrationFormType::class);

        $config = $form->get('accept2fa')->getConfig();

        $this->assertFalse(
            $config->getOption('mapped')
        );

        $constraints = $config->getOption('constraints');

        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(
            IsTrue::class,
            $constraints[0]
        );
    }

    public function testAgreeTermsFieldConfiguration(): void
    {
        // Vérifie la configuration du champ agreeTerms

        $form = $this->factory->create(RegistrationFormType::class);

        $config = $form->get('agreeTerms')->getConfig();

        $this->assertFalse(
            $config->getOption('mapped')
        );

        $constraints = $config->getOption('constraints');

        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(
            IsTrue::class,
            $constraints[0]
        );
    }

    public function testPlainPasswordFieldConfiguration(): void
    {
        // Vérifie la configuration du mot de passe

        $form = $this->factory->create(RegistrationFormType::class);

        $config = $form->get('plainPassword')->getConfig();

        $this->assertFalse(
            $config->getOption('mapped')
        );

        $this->assertSame(
            'password',
            $config->getOption('attr')['id']
        );
    }

    public function testPlainPasswordContainsExpectedConstraints(): void
    {
        // Vérifie les contraintes du mot de passe

        $form = $this->factory->create(RegistrationFormType::class);

        $constraints = $form
            ->get('plainPassword')
            ->getConfig()
            ->getOption('constraints');

        $hasNotBlank = false;
        $hasPasswordStrength = false;

        foreach ($constraints as $constraint) {
            if ($constraint instanceof NotBlank) {
                $hasNotBlank = true;
            }

            if ($constraint instanceof PasswordStrength) {
                $hasPasswordStrength = true;

                $this->assertSame(
                    60,
                    $constraint->minPercent
                );
            }
        }

        $this->assertTrue($hasNotBlank);
        $this->assertTrue($hasPasswordStrength);
    }

    public function testAccept2FaConstraintMessage(): void
    {
        // Vérifie le message de validation du champ accept2fa

        $form = $this->factory->create(RegistrationFormType::class);

        $constraint = $form
            ->get('accept2fa')
            ->getConfig()
            ->getOption('constraints')[0];

        $this->assertSame(
            'Vous devez accepter l’activation du 2FA pour sécuriser votre compte.',
            $constraint->message
        );
    }

    public function testAgreeTermsConstraintMessage(): void
    {
        // Vérifie le message de validation du champ agreeTerms

        $form = $this->factory->create(RegistrationFormType::class);

        $constraint = $form
            ->get('agreeTerms')
            ->getConfig()
            ->getOption('constraints')[0];

        $this->assertSame(
            'Vous devez accepter les conditions d’utilisation.',
            $constraint->message
        );
    }
}
