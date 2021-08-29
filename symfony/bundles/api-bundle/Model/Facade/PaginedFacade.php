<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class PaginedFacade implements FacadeInterface
{
    const ITEMS_PER_PAGE = 25;

    /**
     * Total number of available pages
     *
     * @var int
     */
    protected $totalPages = 1;

    /**
     * Current page requested
     *
     * @var int
     */
    protected $currentPage = 1;

    /**
     * An array of the requested resources
     *
     * @var CollectionFacade
     */
    protected $entries;

    public function __construct()
    {
        $this->entries = new CollectionFacade();
    }

    public static function getExample(Facade $decorates = null) : FacadeInterface
    {
        if (null === $decorates) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new self;
        $child  = $decorates->getClass();

        $facade->totalPages  = 5;
        $facade->currentPage = 2;
        for ($i = 0; $i < 3; $i++) {
            $facade->addEntry(
                $child::getExample($decorates->getDecorates())
            );
        }

        return $facade;
    }

    public function getTotalPages() : int
    {
        return $this->totalPages;
    }

    public function setTotalPages(int $totalPages) : PaginedFacade
    {
        $this->totalPages = $totalPages;

        return $this;
    }

    public function getCurrentPage() : int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage) : PaginedFacade
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    public function getEntries() : CollectionFacade
    {
        return $this->entries;
    }

    public function addEntry(FacadeInterface $facade)
    {
        $this->entries[] = $facade;
    }
}