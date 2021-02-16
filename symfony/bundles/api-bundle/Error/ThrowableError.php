<?php

namespace Bundles\ApiBundle\Error;

use Bundles\ApiBundle\Contracts\ErrorInterface;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\EmptyFacade;
use Bundles\ApiBundle\Model\Facade\ThrowableFacade;
use Symfony\Component\HttpFoundation\Response;

class ThrowableError implements ErrorInterface
{
    private $throwable;

    public function __construct(\Throwable $throwable)
    {
        $this->throwable = $throwable;
    }

    public function getStatus() : int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function getCode() : string
    {
        return '0002';
    }

    public function getMessage() : string
    {
        return 'Internal Server Error';
    }

    public function getContext() : FacadeInterface
    {
        if ('dev' === getenv('APP_ENV')) {
            return new ThrowableFacade($this->throwable);
        }

        return new EmptyFacade();
    }
}