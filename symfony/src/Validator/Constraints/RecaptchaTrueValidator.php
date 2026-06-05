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
    private ?string                  $cachedToken  = null;
    private bool                     $cachedResult = false;

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

        if (!$this->verifyOnce()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    /**
     * reCAPTCHA tokens are single-use at Google: a second verify of the same
     * token returns "timeout-or-duplicate". Memoize per token so this
     * validator is idempotent if invoked more than once per request.
     */
    private function verifyOnce() : bool
    {
        $request = $this->requestStack->getMainRequest();
        $token   = $request ? (string) $request->request->get('g-recaptcha-response') : '';

        if (null !== $this->cachedToken && $this->cachedToken === $token) {
            return $this->cachedResult;
        }

        $this->cachedToken  = $token;
        $this->cachedResult = $this->verifier->verify($request);

        return $this->cachedResult;
    }
}
