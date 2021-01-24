<?php

namespace Bundles\ApiBundle\Model\Documentation;

class PropertyDescription
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var ConstraintDescription[]
     */
    private $constraints = [];

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : PropertyDescription
    {
        $this->name = $name;

        return $this;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : PropertyDescription
    {
        $this->type = $type;

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