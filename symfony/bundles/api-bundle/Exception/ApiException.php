<?php

namespace Bundles\ApiBundle\Exception;

use Bundles\ApiBundle\Contracts\ApiExceptionInterface;
use Bundles\ApiBundle\Contracts\ErrorInterface;

class ApiException extends \RuntimeException implements ApiExceptionInterface
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
