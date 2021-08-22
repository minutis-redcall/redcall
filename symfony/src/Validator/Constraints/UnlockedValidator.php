<?php

namespace App\Validator\Constraints;

use App\Contract\LockableInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UnlockedValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof LockableInterface) {
            throw new UnexpectedTypeException($value, LockableInterface::class);
        }

        if (!$constraint instanceof Unlocked) {
            throw new UnexpectedTypeException($constraint, Unlocked::class);
        }

        if ($value->isLocked()) {
            $this->context->addViolation('This resource is locked.');
        }
    }
}