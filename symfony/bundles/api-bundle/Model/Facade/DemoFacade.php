<?php

namespace Bundles\ApiBundle\Model\Facade;

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