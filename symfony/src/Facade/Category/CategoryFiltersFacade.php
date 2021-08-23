<?php

namespace App\Facade\Category;

use App\Facade\Generic\PageFilterFacade;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class CategoryFiltersFacade extends PageFilterFacade
{
    /**
     * An optional search criteria in order to seek for a category by name
     *
     * @var string|null
     */
    protected $criteria;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = parent::getExample($decorates);

        $facade->setCriteria('vehicl');

        return $facade;
    }

    public function getCriteria() : ?string
    {
        return $this->criteria;
    }

    public function setCriteria(?string $criteria) : CategoryFiltersFacade
    {
        $this->criteria = $criteria;

        return $this;
    }
}