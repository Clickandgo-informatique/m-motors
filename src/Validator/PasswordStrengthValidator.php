<?php
// src/Validator/PasswordStrengthValidator.php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordStrengthValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return; // pas de validation si vide, laisse le NotBlank gérer
        }

        $score = 0;

        // Critères et scores
        if (strlen($value) >= 8) $score += 25;
        if (preg_match('/[a-z]/', $value)) $score += 15;
        if (preg_match('/[A-Z]/', $value)) $score += 20;
        if (preg_match('/\d/', $value)) $score += 20;
        if (preg_match('/[!@#$%^&*]/', $value)) $score += 20;

        if ($score > 100) $score = 100;

        if ($score < $constraint->minPercent) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ percent }}', (string)$constraint->minPercent)
                ->setParameter('{{ score }}', (string)$score)
                ->addViolation();
        }
    }
}
