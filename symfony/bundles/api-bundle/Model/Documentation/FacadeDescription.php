<?php

namespace Bundles\ApiBundle\Model\Documentation;

class FacadeDescription
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var PropertyDescription[]
     */
    private $properties = [];

    /**
     * @var array|null
     */
    private $example;

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : FacadeDescription
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : FacadeDescription
    {
        $this->description = $description;

        return $this;
    }

    public function getProperties() : array
    {
        return $this->properties;
    }

    public function setProperties(array $properties) : FacadeDescription
    {
        $this->properties = $properties;

        return $this;
    }

    public function getExample() : ?array
    {
        return $this->example;
    }

    public function setExample(?array $example) : FacadeDescription
    {
        $this->example = $example;

        return $this;
    }
}