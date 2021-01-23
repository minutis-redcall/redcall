<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class EmptyFacade implements FacadeInterface
{
    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        return new self;
    }
}