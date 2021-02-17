<?php

namespace App\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BadgeFiltersFacade implements FacadeInterface
{
    /**
     * Page number to request
     *
     * @Assert\Range(min = 1)
     *
     * @var int
     */
    private $page = 1;

    /**
     * An optional search criteria in order to seek for a badge by name
     *
     * @var string|null
     */
    private $criteria;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->page = 1;

        return $facade;
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function setPage(int $page) : BadgeFacade
    {
        $this->page     = $page;
        $this->criteria = 'urgen';

        return $this;
    }

    public function getCriteria() : ?string
    {
        return $this->criteria;
    }

    public function setCriteria(?string $criteria) : BadgeFacade
    {
        $this->criteria = $criteria;

        return $this;
    }
}