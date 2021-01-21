<?php

namespace Bundles\ApiBundle\Model\Facade;

class SuccessFacade implements FacadeInterface
{
    private $success = true;

    private $payload;

    public function isSuccess() : bool
    {
        return $this->success;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload(FacadeInterface $payload) : self
    {
        $this->payload = $payload;

        return $this;
    }
}