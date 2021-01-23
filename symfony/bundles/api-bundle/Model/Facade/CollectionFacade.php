<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

/**
 * @Api\Compound
 */
class CollectionFacade extends \ArrayObject implements FacadeInterface
{
    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        if (null === $child) {
            throw new \LogicException('This facade decorates another facade');
        }

        return new self([
            $child->getExample(),
            $child->getExample(),
        ]);
    }
}