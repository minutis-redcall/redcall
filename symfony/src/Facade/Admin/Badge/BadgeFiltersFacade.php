<?php

namespace App\Facade\Admin\Badge;

use App\Facade\PageFilterFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class BadgeFiltersFacade extends PageFilterFacade
{
    /**
     * An optional search criteria in order to seek for a badge by name
     *
     * @var string|null
     */
    protected $criteria;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = parent::getExample($decorates);

        $facade->criteria = 'urgen';

        return $facade;
    }

    public function getCriteria() : ?string
    {
        return $this->criteria;
    }

    public function setCriteria(?string $criteria) : BadgeFiltersFacade
    {
        $this->criteria = $criteria;

        return $this;
    }
}