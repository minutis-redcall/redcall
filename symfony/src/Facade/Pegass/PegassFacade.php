<?php

namespace App\Facade\Pegass;

use App\Entity\Pegass;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

/**
 * A Pegass entity.
 *
 * The Pegass payload is not well documented because it relies on an external provider and backward
 * compatibility is not guaranted.
 */
class PegassFacade implements FacadeInterface
{
    /**
     * Pegass entity type
     *
     * @var string
     */
    private $type;

    /**
     * Remote resource identifier.
     * Used to do the mapping between RedCall and Pegass databases.
     *
     * @var string|null
     */
    private $identifier;

    /**
     * Entities are hierarchized.
     * Volunteers are tied to structures, structures to departments, departments to countries.
     *
     * @var string|null
     */
    private $parentIdentifier;

    /**
     * Raw Pegass content.
     * Built on top of several Pegass api calls.
     *
     * @var object|null
     */
    private $content;

    /**
     * The last cache update date.
     *
     * @var \DateTime
     */
    private $updatedAt;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new static;

        $facade->type             = Pegass::TYPE_VOLUNTEER;
        $facade->identifier       = '00000342302R';
        $facade->parentIdentifier = '|889|';
        $facade->content          = json_decode('{
            "user": {},
            "infos": {},
            "contact": [],
            "actions": [],
            "skills": [],
            "trainings": [],
            "nominations": [],
            "notes": "Pegass payloads are coming from external sources and are subject to change. Please run a call to this endpoint to get a real example."
        }', true);
        $facade->updatedAt        = new \DateTime('2020-12-29 23:48:07');

        return $facade;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : PegassFacade
    {
        $this->type = $type;

        return $this;
    }

    public function getIdentifier() : ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier) : PegassFacade
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getParentIdentifier() : ?string
    {
        return $this->parentIdentifier;
    }

    public function setParentIdentifier(?string $parentIdentifier) : PegassFacade
    {
        $this->parentIdentifier = $parentIdentifier;

        return $this;
    }

    public function getContent() : ?array
    {
        return $this->content;
    }

    public function setContent(?array $content) : PegassFacade
    {
        $this->content = $content;

        return $this;
    }

    public function getUpdatedAt() : \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt) : PegassFacade
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
