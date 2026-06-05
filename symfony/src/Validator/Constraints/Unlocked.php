<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Unlocked extends Constraint
{
    public function getTargets() : array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}