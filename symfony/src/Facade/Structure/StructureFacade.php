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
     * @var string|null
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
     * Structure name shortcut.
     *
     * The structure shortcut will be used in SMS in order for volunteers being tied
     * to several structures to know from which one they are receiving the message.
     *
     * @Assert\Length(max = 32)
     *
     * @var string|null
     */
    protected $shortcut;

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

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->setExternalId('demo-paris');
        $facade->setName('Paris');
        $facade->setShortcut('75');
        $facade->setPresidentExternalId('demo-volunteer');

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

    public function getShortcut() : ?string
    {
        return $this->shortcut;
    }

    public function setShortcut(?string $shortcut) : StructureFacade
    {
        $this->shortcut = $shortcut;

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
}