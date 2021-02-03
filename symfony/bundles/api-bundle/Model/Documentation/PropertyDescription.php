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

    private $child

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : PropertyDescription
    {
        $this->name = $name;

        return $this;
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
}