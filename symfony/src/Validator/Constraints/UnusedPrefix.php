<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UnusedPrefix extends Constraint
{
    public $message = 'form.communication.errors.prefix_already_used';
}