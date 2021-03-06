<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class HelloResponseFacade implements FacadeInterface
{
    /**
     * The provided name.
     *
     * @var string
     */
    private $hello;

    public function __construct(string $world)
    {
        $this->hello = $world;
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new static('Bob');

        return $facade;
    }

    public function getHello() : string
    {
        return $this->hello;
    }
}