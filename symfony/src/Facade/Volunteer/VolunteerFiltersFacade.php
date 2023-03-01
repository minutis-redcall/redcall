<?php

namespace App\Facade\Volunteer;

use App\Facade\Generic\PageFilterFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class VolunteerFiltersFacade extends PageFilterFacade
{
    /**
     * An optional search criteria in order to seek for a volunteer
     *
     * @var string|null
     */
    protected $criteria;

    /**
     * Only search for enabled volunteers (default to true)
     *
     * @var bool|int
     */
    protected $onlyEnabled = true;

    /**
     * Only search for volunteers having RedCall access (default to false)
     *
     * @var bool|int
     */
    protected $onlyUsers = false;

    /**
     * Only search for volunteers being locked
     *
     * @var bool|int
     */
    protected $onlyLocked = false;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = parent::getExample($decorates);

        $facade->criteria = 'martin';

        return $facade;
    }

    public function getCriteria() : ?string
    {
        return $this->criteria;
    }

    public function setCriteria(?string $criteria) : self
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function isOnlyEnabled() : bool
    {
        return $this->onlyEnabled;
    }

    public function setOnlyEnabled(bool $onlyEnabled) : VolunteerFiltersFacade
    {
        $this->onlyEnabled = $onlyEnabled;

        return $this;
    }

    public function isOnlyUsers() : bool
    {
        return $this->onlyUsers;
    }

    public function setOnlyUsers(bool $onlyUsers) : VolunteerFiltersFacade
    {
        $this->onlyUsers = $onlyUsers;

        return $this;
    }

    public function isOnlyLocked() : bool
    {
        return $this->onlyLocked;
    }

    public function setOnlyLocked(bool $onlyLocked) : void
    {
        $this->onlyLocked = $onlyLocked;
    }
}