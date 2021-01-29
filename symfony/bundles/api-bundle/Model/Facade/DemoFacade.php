<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DemoFacade implements FacadeInterface
{
    /**
     * @Assert\NotBlank
     *
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $demo = 'You successfully authenticated!';

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade       = new self;
        $facade->name = 'bob';

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

    public function getDemo() : string
    {
        return $this->demo;
    }
}