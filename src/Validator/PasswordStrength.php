<?php
// src/Validator/PasswordStrength.php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PasswordStrength extends Constraint
{
    public $message = 'Le mot de passe doit atteindre au moins {{ percent }}% de complexité. Actuellement : {{ score }}%.';
    public int $minPercent = 60;
}
