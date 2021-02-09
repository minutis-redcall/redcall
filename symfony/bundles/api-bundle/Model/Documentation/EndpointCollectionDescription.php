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
        $endpoints = $this->endpoints;

        usort($endpoints, function (EndpointDescription $a, EndpointDescription $b) {
            return $a->getPriority() <=> $b->getPriority();
        });

        $categories = [];
        foreach ($endpoints as $endpoint) {
            if (!in_array($endpoint->getCategory(), $categories)) {
                $categories[$endpoint->getCategory()] = [];
            }
            $categories[$endpoint->getCategory()][] = $endpoint;
        }

        $this->endpoints = call_user_func_array('array_merge', array_values($categories));
    }
}