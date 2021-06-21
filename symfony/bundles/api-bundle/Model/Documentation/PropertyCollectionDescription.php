<?php

namespace Bundles\ApiBundle\Model\Documentation;

class PropertyCollectionDescription
{
    /**
     * @var PropertyDescription[]
     */
    private $properties = [];

    /**
     * @var bool
     */
    private $collection = false;

    public function add(PropertyDescription $property) : self
    {
        $this->properties[$property->getName()] = $property;

        return $this;
    }

    public function get(string $name) : ?PropertyDescription
    {
        return $this->properties[$name] ?? null;
    }

    public function all() : array
    {
        return $this->properties;
    }

    public function isCollection() : bool
    {
        return $this->collection;
    }

    public function setCollection(bool $collection) : PropertyCollectionDescription
    {
        $this->collection = $collection;

        return $this;
    }

    public function sort()
    {
        ksort($this->properties);
    }
}
