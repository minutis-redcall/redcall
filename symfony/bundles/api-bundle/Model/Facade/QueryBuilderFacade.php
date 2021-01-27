<?php

namespace Bundles\ApiBundle\Model\Facade;

use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class QueryBuilderFacade extends PaginedFacade
{
    public function __construct(QueryBuilder $qb, int $currentPage, callable $transformer)
    {
        parent::__construct();

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
}
