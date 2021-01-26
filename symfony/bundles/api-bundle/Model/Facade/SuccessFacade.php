<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class SuccessFacade implements FacadeInterface
{
    /**
     * @var bool
     */
    private $success = true;

    /**
     * @var FacadeInterface
     */
    private $payload;

    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        if (null === $child) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new self;

        $facade->payload = $child;

        return $facade;
    }

    public function isSuccess() : bool
    {
        return $this->success;
    }

    public function getPayload() : FacadeInterface
    {
        return $this->payload;
    }

    public function setPayload(FacadeInterface $payload) : self
    {
        $this->payload = $payload;

        return $this;
    }
}