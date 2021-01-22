<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class CollectionFacade extends \ArrayObject implements FacadeInterface
{
    static public function getExample() : FacadeInterface
    {
        return new self([
            'foo' => 'bar',
        ]);
    }
}