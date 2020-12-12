<?php

namespace Bundles\ApiBundle\Exception;

use Bundles\ApiBundle\Enum\Error;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiAuthenticationException extends AuthenticationException
{
    /**
     * @var Error
     */
    private $error;

    public function __construct(Error $error)
    {
        $this->error = $error;
    }

    public function getError() : Error
    {
        return $this->error;
    }
}
