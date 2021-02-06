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

    public function sort()
    {
        usort($this->endpoints, function (EndpointDescription $a, EndpointDescription $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }
}