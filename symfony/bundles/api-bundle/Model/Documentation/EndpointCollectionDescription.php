<?php

namespace Bundles\ApiBundle\Model\Documentation;

class EndpointCollectionDescription
{
    /**
     * @var EndpointDescription[]
     */
    private $endpoints = [];

    public function getEndpoints() : array
    {
        return $this->endpoints;
    }

    public function add(EndpointDescription $endpoint) : self
    {
        $this->endpoints[] = $endpoint;

        return $this;
    }

    public function sort()
    {
        usort($this->endpoints, function (EndpointDescription $a, EndpointDescription $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }

    public function getPriority() : int
    {
        return reset($this->endpoints)->getPriority();
    }
}