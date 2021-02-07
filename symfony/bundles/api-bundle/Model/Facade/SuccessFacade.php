<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
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

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        if (null === $decorates) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new self;

        $child           = $decorates->class;
        $facade->payload = $child::getExample($decorates->decorates);

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