<?php

namespace App\Validator\Constraints;

use App\Captcha\CaptchaVerifierInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RecaptchaTrueValidator extends ConstraintValidator
{
    private CaptchaVerifierInterface $verifier;
    private RequestStack             $requestStack;
    private bool                     $enabled;

    public function __construct(CaptchaVerifierInterface $verifier, RequestStack $requestStack, bool $enabled)
    {
        $this->verifier     = $verifier;
        $this->requestStack = $requestStack;
        $this->enabled      = $enabled;
    }

    public function validate(mixed $value, Constraint $constraint) : void
    {
        if (!$constraint instanceof RecaptchaTrue) {
            throw new UnexpectedTypeException($constraint, RecaptchaTrue::class);
        }

        if (!$this->enabled) {
            return;
        }

        if (!$this->verifier->verify($this->requestStack->getMainRequest())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
