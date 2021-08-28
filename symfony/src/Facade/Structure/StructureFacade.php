<?php

namespace App\Facade\Structure;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class StructureFacade implements FacadeInterface
{
    /**
     * An unique identifier for the structure.
     *
     * You can use a random UUID, a name or the same identifier as in your own application.
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(max = 64)
     *
     * @var string
     */
    protected $externalId;

    /**
     * Identifier of the parent structure.
     *
     * Structures can be organized in a hierarchy, so that a region can trigger all
     * its departments, a department can trigger all its cities, a city can trigger
     * all its districts.
     *
     * For example, in France, "DT de Paris" can trigger "UL de Paris 1&2", "UL de
     * Paris 3&4" and 14 others.
     *
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $parentExternalId;

    /**
     * Structure name.
     *
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $name;

    /**
     * Structure representative's external id.
     *
     * In the French Red Cross, every structure has a president, elected
     * every 4 years. Providing the structure's representative can be useful
     * for redcall users (data deletion request, question about people allowed
     * to trigger its volunteers...)
     *
     * @Assert\Length(max = 64)
     *
     * @var string|null
     */
    protected $presidentExternalId;

    /**
     * Whether the structure is locked or not.
     *
     * A "locked" structure cannot be modified through APIs, this is useful when
     * there are divergences between your own database and the RedCall database.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $locked;

    /**
     * Whether the structure is enabled or not.
     *
     * RedCall resources (categories, badges, structures, volunteers) may have relations with
     * other sensible parts of the application (triggers, communications, messages, answers, etc.),
     * so it may be safer to disable them instead of deleting them and creating database inconsistencies.
     *
     * In order to comply with the General Data Protection Regulation (GDPR), resources containing
     * private information can be anonymized.
     *
     * @Assert\Choice(choices={false, true})
     *
     * @var bool|null
     */
    protected $enabled;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->setExternalId('demo-paris');
        $facade->setName('Paris');
        $facade->setPresidentExternalId('demo-volunteer');
        $facade->setLocked(false);
        $facade->setEnabled(true);

        return $facade;
    }

    public function getExternalId() : ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : StructureFacade
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getParentExternalId() : ?string
    {
        return $this->parentExternalId;
    }

    public function setParentExternalId(?string $parentExternalId) : StructureFacade
    {
        $this->parentExternalId = $parentExternalId;

        return $this;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : StructureFacade
    {
        $this->name = $name;

        return $this;
    }

    public function getPresidentExternalId() : ?string
    {
        return $this->presidentExternalId;
    }

    public function setPresidentExternalId(?string $presidentExternalId) : StructureFacade
    {
        $this->presidentExternalId = $presidentExternalId;

        return $this;
    }

    public function getLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked) : StructureFacade
    {
        $this->locked = $locked;

        return $this;
    }

    public function getEnabled() : ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled) : StructureFacade
    {
        $this->enabled = $enabled;

        return $this;
    }
}