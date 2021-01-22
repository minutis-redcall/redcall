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