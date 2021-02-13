<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class HelloRequestFacade implements FacadeInterface
{
    /**
     * What is your name?
     *
     * @Assert\NotBlank
     *
     * @var string
     */
    private $name;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade       = new self;
        $facade->name = 'Bob';

        return $facade;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }
}