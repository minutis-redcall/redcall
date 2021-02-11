<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class ErrorFacade implements FacadeInterface
{
    /**
     * Always false on error responses
     *
     * @var bool
     */
    private $success = false;

    /**
     * A code for the given error.
     *
     * @var string
     */
    private $code;

    /**
     * A human readable error message.
     *
     * @var string
     */
    private $message;

    /**
     * Context that will help understand and fix the error.
     *
     * @var FacadeInterface|null
     */
    private $context;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->code    = '1234';
        $facade->message = 'Sample message';

        if ($decorates) {
            $child           = $decorates->getClass();
            $facade->context = $child::getExample($decorates->getDecorates());
        } else {
            $facade->context = new EmptyFacade();
        }

        return $facade;
    }

    public function isSuccess() : bool
    {
        return $this->success;
    }

    public function getCode() : string
    {
        return $this->code;
    }

    public function setCode(string $code) : self
    {
        $this->code = $code;

        return $this;
    }

    public function getMessage() : string
    {
        return $this->message;
    }

    public function setMessage(string $message) : self
    {
        $this->message = $message;

        return $this;
    }

    public function getContext() : FacadeInterface
    {
        return $this->context;
    }

    public function setContext(FacadeInterface $context) : self
    {
        $this->context = $context;

        return $this;
    }
}