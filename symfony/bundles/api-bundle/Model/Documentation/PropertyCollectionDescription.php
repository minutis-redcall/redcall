<?php

namespace Bundles\ApiBundle\Model\Documentation;

class PropertyCollectionDescription
{
    /**
     * @var PropertyDescription[]
     */
    private $properties = [];

    public function add(PropertyDescription $property) : self
    {
        $this->properties[] = $property;

        return $this;
    }
}
