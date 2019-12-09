<?php

namespace App\Validator\Constraints;

use App\Repository\CommunicationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UnusedPrefixValidator extends ConstraintValidator
{
    private $communicationRepository;

    public function __construct(CommunicationRepository $communicationRepository)
    {
        $this->communicationRepository = $communicationRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UnusedPrefix) {
            throw new UnexpectedTypeException($constraint, UnusedPrefix::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (in_array($value, $this->communicationRepository->getTakenPrefixes())) {
            $this->context->buildViolation($constraint->message)
                          ->setParameter('{{ string }}', $value)
                          ->addViolation();
        }
    }
}
