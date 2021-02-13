<?php

namespace Bundles\ApiBundle\Model\Documentation;

class CategoryDescription
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var EndpointCollectionDescription
     */
    private $endpoints;

    public function __construct()
    {
        $this->endpoints = new EndpointCollectionDescription();
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : CategoryDescription
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : CategoryDescription
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function setDescription(string $description) : CategoryDescription
    {
        $this->description = $description;

        return $this;
    }

    public function getEndpoints() : EndpointCollectionDescription
    {
        return $this->endpoints;
    }

    public function setEndpoints(EndpointCollectionDescription $endpoints) : CategoryDescription
    {
        $this->endpoints = $endpoints;

        return $this;
    }

    public function getEndpoint(int $index) : ?EndpointDescription
    {
        return $this->endpoints->getEndpoint($index);
    }

    public function getPriority() : int
    {
        return $this->endpoints->getPriority();
    }
}