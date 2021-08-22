<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Unlocked extends Constraint
{
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}