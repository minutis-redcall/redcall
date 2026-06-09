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
    public function validate(mixed $value, Constraint $constraint) : void
    {
        if (!$constraint instanceof Phone) {
            throw new UnexpectedTypeException($constraint, Phone::class);
        }

        if (!$value instanceof PhoneInterface) {
            throw new UnexpectedTypeException($value, PhoneInterface::class);
        }

        // An empty phone number is invalid (typically a row added in the
        // form but never filled, or one whose e164 was never computed by
        // the client-side validator).
        $e164 = $value->getE164();
        if (null === $e164 || '' === $e164) {
            $this->context
                ->buildViolation(
                    $this->translator->trans('phone_card.error_invalid')
                )
                ->atPath('editor')
                ->addViolation();

            return;
        }

        // This phone number is invalid
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneUtil->parse($e164, \App\Entity\Phone::DEFAULT_LANG);
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