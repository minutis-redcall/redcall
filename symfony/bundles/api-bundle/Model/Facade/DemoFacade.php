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
    private $name = 'bob';

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