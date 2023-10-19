<?php

namespace App\Validator\Constraints;

use App\Contract\PhoneInterface;
use App\Manager\PhoneManager;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneValidator extends ConstraintValidator
{
    /**
     * @var PhoneManager
     */
    private $phoneManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(PhoneManager $phoneManager, TranslatorInterface $translator)
    {
        $this->phoneManager = $phoneManager;
        $this->translator   = $translator;
    }

    /**
     * @param PhoneInterface $value
     * @param Constraint     $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Phone) {
            throw new UnexpectedTypeException($constraint, Phone::class);
        }

        if (!$value instanceof PhoneInterface) {
            throw new UnexpectedTypeException($value, PhoneInterface::class);
        }

        // This phone number is invalid
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneUtil->parse($value->getE164(), \App\Entity\Phone::DEFAULT_LANG);
        } catch (NumberParseException $e) {
            $this->context
                ->buildViolation(
                    $this->translator->trans('phone_card.error_invalid')
                )
                ->atPath('editor')
                ->addViolation();

            return;
        }
    }
}