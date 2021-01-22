<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class EmptyFacade implements FacadeInterface
{
    static public function getExample() : FacadeInterface
    {
        return new self;
    }
}