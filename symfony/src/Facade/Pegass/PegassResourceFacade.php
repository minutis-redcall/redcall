<?php

namespace App\Facade\Pegass;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use App\Entity\Pegass;
use Symfony\Component\Validator\Constraints as Assert;

class PegassResourceFacade implements FacadeInterface
{
    /**
     * Type of required Pegass entity
     *
     * @Assert\Choice(choices = {
     *     Pegass::TYPE_STRUCTURE,
     *     Pegass::TYPE_VOLUNTEER
     * })
     *
     * @var string|null
     */
    private $type;

    /**
     * Identifier of a resource (aka. "nivol" for a volunteer)
     *
     * @Assert\Length(max = 64)
     *
     * @var scalar|null
     */
    private $identifier;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade->type       = Pegass::TYPE_VOLUNTEER;
        $facade->identifier = null;

        return $facade;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function setType(?string $type) : self
    {
        $this->type = $type;

        return $this;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier) : self
    {
        $this->identifier = $identifier;

        return $this;
    }
}