<?php

namespace App\Validator\Constraints;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class WhitelistedRedirectUrlValidator extends ConstraintValidator
{
    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof WhitelistedRedirectUrl) {
            throw new UnexpectedTypeException($constraint, WhitelistedRedirectUrl::class);
        }

        if (null === $value) {
            return;
        }

        $whitelist = $this->parameters->get('whitelisted_base_redirect_urls');

        if (!is_array($whitelist)) {
            throw new \LogicException('URL whitelists are not configured in parameters.yaml in this environment');
        }

        foreach ($whitelist as $baseUrl) {
            if (\str_starts_with($value, $baseUrl)) {
                return;
            }
        }

        $this->context
            ->buildViolation('whitelist.invalid_redirect_url')
            ->addViolation();
    }
}
