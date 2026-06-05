<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class RecaptchaTrue extends Constraint
{
    public string $message = 'recaptcha.invalid';
}
