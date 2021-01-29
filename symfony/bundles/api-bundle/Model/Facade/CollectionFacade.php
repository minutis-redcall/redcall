<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class CollectionFacade extends \ArrayObject implements FacadeInterface
{
    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        if (null === $decorates) {
            throw new \LogicException('This facade decorates another facade');
        }

        $child = $decorates->class;

        $facade   = new self;
        $facade[] = $child::getExample($decorates->decorates);
        $facade[] = $child::getExample($decorates->decorates);

        return $facade;
    }
}