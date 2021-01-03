<?php

namespace Bundles\ChartBundle\Manager;

use Bundles\ChartBundle\Repository\QueryRepository;

class QueryManager
{
    /**
     * @var QueryRepository
     */
    private $queryRepository;

    public function __construct(QueryRepository $queryRepository)
    {
        $this->queryRepository = $queryRepository;
    }


}