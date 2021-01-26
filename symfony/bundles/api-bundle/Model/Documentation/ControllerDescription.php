<?php

namespace Bundles\ApiBundle\Model\Documentation;

use Bundles\ApiBundle\Annotation\Endpoint;
use Symfony\Component\Routing\Route;

class ControllerDescription
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    /**
     * @var Endpoint
     */
    private $annotation;

    public function __construct(Route $route, string $class, string $method, Endpoint $annotation)
    {
        $this->route      = $route;
        $this->class      = $class;
        $this->method     = $method;
        $this->annotation = $annotation;
    }

    public function getRoute() : Route
    {
        return $this->route;
    }

    public function getClass() : string
    {
        return $this->class;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getAnnotation() : Endpoint
    {
        return $this->annotation;
    }
}