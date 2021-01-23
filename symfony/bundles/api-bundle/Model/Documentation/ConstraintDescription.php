<?php

namespace Bundles\ApiBundle\Model\Documentation;

class ConstraintDescription
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options = [];

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : ConstraintDescription
    {
        $this->name = $name;

        return $this;
    }

    public function getOptions() : array
    {
        return $this->options;
    }

    public function setOptions(array $options) : ConstraintDescription
    {
        $this->options = $options;

        return $this;
    }
}