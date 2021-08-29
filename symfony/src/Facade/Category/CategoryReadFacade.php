<?php

namespace App\Facade\Category;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class CategoryReadFacade extends CategoryFacade
{
    /**
     * Whether the category is locked or not.
     *
     * A "locked" category cannot be modified through APIs, this is useful when
     * there are divergences between your own database and the RedCall database.
     *
     * @var bool|null
     */
    protected $locked;

    /**
     * Whether the category is enabled or not.
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

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = new self;
        foreach (get_object_vars(parent::getExample($decorates)) as $property => $value) {
            $facade->{$property} = $value;
        }

        $facade->locked  = false;
        $facade->enabled = true;

        return $facade;
    }

    public function isLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked) : CategoryFacade
    {
        $this->locked = $locked;

        return $this;
    }

    public function isEnabled() : ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled) : CategoryFacade
    {
        $this->enabled = $enabled;

        return $this;
    }
}