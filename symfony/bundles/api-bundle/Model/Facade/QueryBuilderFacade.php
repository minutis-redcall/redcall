<?php

namespace Bundles\ApiBundle\Model\Facade;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @Api\Compound
 */
class QueryBuilderFacade implements FacadeInterface
{
    const ITEMS_PER_PAGE = 25;

    /**
     * @var int
     */
    private $totalPages;

    /**
     * @var int
     */
    private $currentPage;

    /**
     * @var CollectionFacade
     */
    private $entries;

    public function __construct(QueryBuilder $qb, int $currentPage, callable $transformer)
    {
        $pager = new Pagerfanta(
            new QueryAdapter($qb)
        );

        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage(self::ITEMS_PER_PAGE);
        $pager->setCurrentPage($currentPage);

        $this->totalPages  = $pager->getNbPages();
        $this->currentPage = $pager->getCurrentPage();

        foreach ($pager->getCurrentPageResults() as $item) {
            $this->addEntry(
                $transformer($item)
            );
        }
    }

    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        if (null === $child) {
            throw new \LogicException('This facade decorates another facade');
        }

        $facade = new self;

        $facade->currentPage = 2;
        $facade->totalPages  = 5;
        for ($i = 0; $i < self::ITEMS_PER_PAGE; $i++) {
            $facade->addEntry($child);
        }

        return $facade;
    }

    public function getTotalPages() : int
    {
        return $this->totalPages;
    }

    public function getCurrentPage() : int
    {
        return $this->currentPage;
    }

    public function getEntries() : CollectionFacade
    {
        return $this->entries;
    }

    private function addEntry(FacadeInterface $facade)
    {
        if (null === $this->entries) {
            $this->entries = new CollectionFacade();
        }

        $this->entries[] = $facade;
    }
}