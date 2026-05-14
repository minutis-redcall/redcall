<?php

namespace App\Validator\Constraints;

use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RecaptchaTrueValidator extends ConstraintValidator
{
    private ReCaptcha     $recaptcha;
    private RequestStack  $requestStack;
    private bool          $enabled;

    public function __construct(ReCaptcha $recaptcha, RequestStack $requestStack, bool $enabled)
    {
        $this->recaptcha    = $recaptcha;
        $this->requestStack = $requestStack;
        $this->enabled      = $enabled;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof RecaptchaTrue) {
            throw new UnexpectedTypeException($constraint, RecaptchaTrue::class);
        }

        if (!$this->enabled) {
            return;
        }

        $request = $this->requestStack->getMainRequest();
        $token   = $request ? $request->request->get('g-recaptcha-response') : null;
        $ip      = $request ? $request->getClientIp() : null;

        $response = $this->recaptcha->verify((string) $token, $ip);

        if (!$response->isSuccess()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
