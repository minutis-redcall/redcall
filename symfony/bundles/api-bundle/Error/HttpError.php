<?php

namespace Bundles\ApiBundle\Error;

use Bundles\ApiBundle\Contracts\ErrorInterface;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\EmptyFacade;
use Bundles\ApiBundle\Model\Facade\ThrowableFacade;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpError implements ErrorInterface
{
    /**
     * @var HttpException
     */
    private $exception;

    public function __construct(HttpException $exception)
    {
        $this->exception = $exception;
    }

    public function getStatus() : int
    {
        return $this->exception->getStatusCode();
    }

    public function getCode() : string
    {
        return '0001';
    }

    public function getMessage() : string
    {
        if ('dev' === getenv('APP_ENV')) {
            return $this->exception->getMessage();
        }

        return 'Internal Server Error';
    }

    public function getContext() : FacadeInterface
    {
        if ('dev' === getenv('APP_ENV')) {
            return new ThrowableFacade($this->exception);
        }

        return new EmptyFacade();
    }
}