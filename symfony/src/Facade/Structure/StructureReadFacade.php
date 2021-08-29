<?php

namespace App\Facade\Structure;

use App\Facade\Resource\StructureResourceFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;

class StructureReadFacade extends StructureFacade
{
    /**
     * Number of volunteers that can be triggered in the given structure.
     *
     * These people will receive messages when the structure will be selected.
     *
     * @var int
     */
    protected $volunteersCount = 0;

    /**
     * Number of volunteers that can trigger in the given structure.
     *
     * These RedCall users can select this structure in order to send messages
     * to its volunteers.
     *
     * @var int
     */
    protected $usersCount = 0;

    /**
     * The parent structure.
     *
     * A local unit may be attached to a territory, which itself is attached to a department,
     * then a region, or a full country.
     *
     * @var StructureResourceFacade|null
     */
    protected $parentStructure;

    /**
     * Chlidren structures if any.
     *
     * People having access to that structure can trigger any of its children and sub-children.
     *
     * @var StructureResourceFacade[]
     */
    protected $childrenStructures;

    /**
     * Whether the structure is locked or not.
     *
     * A "locked" structure cannot be modified through APIs, this is useful when
     * there are divergences between your own database and the RedCall database.
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
     * @var bool|null
     */
    protected $enabled;

    public function __construct()
    {
        $this->childrenStructures = new CollectionFacade();
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = new self;
        foreach (get_object_vars(parent::getExample($decorates)) as $property => $value) {
            $facade->{$property} = $value;
        }

        $facade->setVolunteersCount(123);
        $facade->setUsersCount(7);
        $facade->setParentStructure(
            StructureResourceFacade::getExample()
        );
        $facade->setLocked(false);
        $facade->setEnabled(true);

        return $facade;
    }

    public function getVolunteersCount() : int
    {
        return $this->volunteersCount;
    }

    public function setVolunteersCount(int $volunteersCount) : StructureReadFacade
    {
        $this->volunteersCount = $volunteersCount;

        return $this;
    }

    public function getUsersCount() : int
    {
        return $this->usersCount;
    }

    public function setUsersCount(int $usersCount) : StructureReadFacade
    {
        $this->usersCount = $usersCount;

        return $this;
    }

    public function getParentStructure() : ?StructureResourceFacade
    {
        return $this->parentStructure;
    }

    public function setParentStructure(?StructureResourceFacade $parentStructure) : StructureReadFacade
    {
        $this->parentStructure = $parentStructure;

        return $this;
    }

    public function getChildrenStructures() : CollectionFacade
    {
        return $this->childrenStructures;
    }

    public function addChildrenStructure(StructureResourceFacade $facade)
    {
        $this->childrenStructures[] = $facade;

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