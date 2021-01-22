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

    static public function getExample() : FacadeInterface
    {
        $facade = new self;

        $facade->code    = '1234';
        $facade->payload = CollectionFacade::getExample();

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