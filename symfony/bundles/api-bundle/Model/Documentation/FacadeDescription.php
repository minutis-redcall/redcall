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
     * @var mixed
     */
    private $example;

    /**
     * @var int
     */
    private $statusCode;

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

    public function getProperties() : PropertyCollectionDescription
    {
        return $this->properties;
    }

    public function setProperties(PropertyCollectionDescription $properties) : FacadeDescription
    {
        $this->properties = $properties;

        return $this;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function setExample($example) : FacadeDescription
    {
        $this->example = $example;

        return $this;
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode) : FacadeDescription
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}