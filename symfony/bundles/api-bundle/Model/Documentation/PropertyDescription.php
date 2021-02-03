<?php

namespace Bundles\ApiBundle\Model\Documentation;

class PropertyDescription
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var TypeDescription[]
     */
    private $types = [];

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var ConstraintDescription[]
     */
    private $constraints = [];

    /**
     * @var PropertyDescription|null
     */
    private $parent;

    /**
     * @var PropertyCollectionDescription|null
     */
    private $children;

    /**
     * @var bool
     */
    private $collection = false;

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : PropertyDescription
    {
        $this->name = $name;

        return $this;
    }

    public function getFullname() : string
    {
        if ($this->parent && $this->parent->isCollection()) {
            $name = sprintf('%s[].%s', $this->getParent()->getFullname(), $this->getName());
        } elseif ($this->parent) {
            $name = sprintf('%s.%s', $this->getParent()->getFullname(), $this->getName());
        } else {
            $name = $this->getName();
        }

        return $name;
    }

    public function getTypes() : array
    {
        return $this->types;
    }

    public function addType(TypeDescription $type) : PropertyDescription
    {
        $this->types[] = $type;

        return $this;
    }

    public function getTitle() : ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title) : PropertyDescription
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : PropertyDescription
    {
        $this->description = $description;

        return $this;
    }

    public function getConstraints() : array
    {
        return $this->constraints;
    }

    public function setConstraints(array $constraints) : PropertyDescription
    {
        $this->constraints = $constraints;

        return $this;
    }

    public function getParent() : ?PropertyDescription
    {
        return $this->parent;
    }

    public function setParent(?PropertyDescription $parent) : PropertyDescription
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren() : ?PropertyCollectionDescription
    {
        return $this->children;
    }

    public function setChildren(?PropertyCollectionDescription $children) : PropertyDescription
    {
        $this->children = $children;

        return $this;
    }

    public function isCollection() : bool
    {
        return $this->collection;
    }

    public function setCollection(bool $collection) : PropertyDescription
    {
        $this->collection = $collection;

        return $this;
    }
}