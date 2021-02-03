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
        $this->properties[$property->getName()] = $property;

        return $this;
    }

    public function get(string $name) : ?PropertyDescription
    {
        return $this->properties[$name] ?? null;
    }
}
