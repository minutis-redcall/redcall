<?php

namespace Bundles\ApiBundle\Exception;

use Bundles\ApiBundle\Contracts\ApiExceptionInterface;
use Bundles\ApiBundle\Contracts\ErrorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiAuthenticationException extends AuthenticationException implements ApiExceptionInterface
{
    private $error;

    public function __construct(ErrorInterface $error, \Throwable $previous = null)
    {
        parent::__construct($error->getMessage(), $error->getCode(), $previous);

        $this->error = $error;
    }

    public function getError() : ErrorInterface
    {
        return $this->error;
    }
}
