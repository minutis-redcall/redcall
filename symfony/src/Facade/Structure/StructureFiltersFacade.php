<?php

namespace App\Facade\Structure;

use App\Facade\Generic\PageFilterFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class StructureFiltersFacade extends PageFilterFacade
{
    /**
     * An optional search criteria in order to seek for a structure
     *
     * @var string|null
     */
    protected $criteria;

    /**
     * Only search for enabled structures (default to true)
     *
     * @var bool|int
     */
    protected $onlyEnabled = true;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        /** @var self $facade */
        $facade = parent::getExample($decorates);

        $facade->criteria = 'pari';

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

    public function setOnlyEnabled(bool $onlyEnabled) : StructureFiltersFacade
    {
        $this->onlyEnabled = $onlyEnabled;

        return $this;
    }
}