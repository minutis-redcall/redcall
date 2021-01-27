<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;

class PaginedFacade implements FacadeInterface
{
    const ITEMS_PER_PAGE = 25;

    /**
     * @var int
     */
    protected $totalPages;

    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @var CollectionFacade
     */
    protected $entries;

    public function __construct()
    {
        $this->entries = new CollectionFacade();
    }

    public static function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        if (null === $child) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new self;

        $facade->totalPages  = 5;
        $facade->currentPage = 2;
        for ($i = 0; $i < self::ITEMS_PER_PAGE; $i++) {
            $facade->addEntry($child);
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

    protected function addEntry(FacadeInterface $facade)
    {
        $this->entries[] = $facade;
    }
}