<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class CollectionFacade extends \ArrayObject implements FacadeInterface
{
    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        if (null === $child) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new self;

        $facade[] = $child;
        $facade[] = $child;

        return $facade;
    }
}