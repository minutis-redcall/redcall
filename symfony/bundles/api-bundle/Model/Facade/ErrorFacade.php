<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class ErrorFacade implements FacadeInterface
{
    /**
     * @var bool
     */
    private $success = false;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $context;

    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        if (null === $child) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new self;

        $facade->code    = '1234';
        $facade->message = 'Sample message';
        $facade->context = $child;

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