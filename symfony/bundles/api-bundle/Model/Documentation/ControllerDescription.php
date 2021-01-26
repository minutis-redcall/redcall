<?php

namespace Bundles\ApiBundle\Model\Documentation;

use Bundles\ApiBundle\Annotation\Endpoint;

class ControllerDescription
{
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

    public function __construct(string $class, string $method, Endpoint $annotation)
    {
        $this->class      = $class;
        $this->method     = $method;
        $this->annotation = $annotation;
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