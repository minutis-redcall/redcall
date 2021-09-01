<?php

namespace App\Facade\Volunteer;

use App\Facade\Phone\PhoneFacade;
use App\Facade\Phone\PhoneReadFacade;
use App\Facade\Resource\BadgeResourceFacade;
use App\Facade\Resource\StructureResourceFacade;
use App\Facade\Resource\UserResourceFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\ApiBundle\Model\Facade\CollectionFacade;

class VolunteerReadFacade extends VolunteerFacade
{
    /**
     * Structures from which volunteer can be triggered
     *
     * @var StructureResourceFacade[]
     */
    protected $structures;

    /**
     * Volunteer phone numbers
     *
     * @var PhoneReadFacade[]
     */
    protected $phones;

    /**
     * Volunteer badges
     *
     * @var BadgeResourceFacade[]
     */
    protected $badges;

    /**
     * A RedCall user resource if the volunteer can trigger people.
     *
     * @var UserResourceFacade|null
     */
    protected $user;

    /**
     * Whether the volunteer is locked or not.
     *
     * A "locked" volunteer cannot be modified through APIs, this is useful when
     * there are divergences between your own database and the RedCall database.
     *
     * @var bool|null
     */
    protected $locked;

    /**
     * Whether the volunteer is enabled or not.
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
        $this->structures = new CollectionFacade();
        $this->phones     = new CollectionFacade();
        $this->badges     = new CollectionFacade();
    }

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = new self;
        foreach (get_object_vars(parent::getExample($decorates)) as $property => $value) {
            $facade->{$property} = $value;
        }

        $facade->addStructure(StructureResourceFacade::getExample());
        $facade->addPhone(PhoneReadFacade::getExample());
        $facade->addBadge(BadgeResourceFacade::getExample());

        return $facade;
    }

    public function getStructures() : CollectionFacade
    {
        return $this->structures;
    }

    public function addStructure(StructureResourceFacade $facade)
    {
        $this->structures[] = $facade;
    }

    public function getPhones() : CollectionFacade
    {
        return $this->phones;
    }

    public function addPhone(PhoneFacade $facade)
    {
        $this->phones[] = $facade;
    }

    public function getBadges() : CollectionFacade
    {
        return $this->badges;
    }

    public function addBadge(BadgeResourceFacade $badge)
    {
        $this->badges[] = $badge;

        return $this;
    }

    public function getUser() : ?UserResourceFacade
    {
        return $this->user;
    }

    public function setUser(?UserResourceFacade $user) : VolunteerReadFacade
    {
        $this->user = $user;

        return $this;
    }

    public function getLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked) : VolunteerReadFacade
    {
        $this->locked = $locked;

        return $this;
    }

    public function getEnabled() : ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled) : VolunteerReadFacade
    {
        $this->enabled = $enabled;

        return $this;
    }
}