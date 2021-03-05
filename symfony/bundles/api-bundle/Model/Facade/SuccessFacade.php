<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class SuccessFacade implements FacadeInterface
{
    /**
     * Always true on successful responses
     *
     * @var bool
     */
    private $success = true;

    /**
     * Payload of the requested resource
     *
     * @var FacadeInterface
     */
    private $payload;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        if (null === $decorates) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new static;

        $child           = $decorates->getClass();
        $facade->payload = $child::getExample($decorates->getDecorates());

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