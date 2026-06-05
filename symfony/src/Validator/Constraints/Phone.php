<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Phone extends Constraint
{
    public function getTargets() : array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
