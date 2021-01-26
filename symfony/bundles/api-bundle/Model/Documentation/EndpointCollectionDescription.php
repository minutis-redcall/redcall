<?php

namespace Bundles\ApiBundle\Model\Documentation;

class EndpointCollectionDescription
{
    /**
     * @var EndpointDescription[]
     */
    private $endpoints = [];

    public function add(EndpointDescription $endpoint) : self
    {
        $this->endpoints[] = $endpoint;

        return $this;
    }
}