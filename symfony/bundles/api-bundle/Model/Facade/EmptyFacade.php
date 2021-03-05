<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class EmptyFacade implements FacadeInterface
{
    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        return new static;
    }
}