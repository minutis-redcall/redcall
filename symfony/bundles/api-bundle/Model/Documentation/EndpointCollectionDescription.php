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
        $this->endpoints[$endpoint->getId()] = $endpoint;

        return $this;
    }

    public function sort()
    {
        uasort($this->endpoints, function (EndpointDescription $a, EndpointDescription $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }

    public function getPriority() : int
    {
        return reset($this->endpoints)->getPriority();
    }

    public function getEndpoint(string $methodName) : ?EndpointDescription
    {
        return $this->endpoints[$methodName] ?? $this->endpoints[sha1($methodName)] ?? null;
    }
}