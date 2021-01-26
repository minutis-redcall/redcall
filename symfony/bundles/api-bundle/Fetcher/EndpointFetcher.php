<?php

namespace Bundles\ApiBundle\Fetcher;

use Bundles\ApiBundle\Model\Documentation\ControllerDescription;
use Bundles\ApiBundle\Model\Documentation\EndpointDescription;

class EndpointFetcher
{
    public function fetch(ControllerDescription $controller) : EndpointDescription
    {
        $endpoint = new EndpointDescription();

        $endpoint->setPriority($controller->getAnnotation()->priority);

        return $endpoint;
    }
}