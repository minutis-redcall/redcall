<?php

namespace App\Facade\Pegass;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PegassFiltersFacade extends PegassResourceFacade
{
    /**
     * Page number to request
     *
     * @Assert\Range(min = 1)
     *
     * @var int
     */
    private $page = 1;

    static public function getExample(Facade $decorates = null) : FacadeInterface
    {
        $facade = parent::getExample($decorates);

        $facade->page = 1;

        return $facade;
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function setPage(int $page) : PegassFiltersFacade
    {
        $this->page = $page;

        return $this;
    }
}