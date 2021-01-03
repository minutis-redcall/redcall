<?php

namespace Bundles\ChartBundle\Manager;

use Bundles\ChartBundle\Repository\PageRepository;

class PageManager
{
    /**
     * @var PageRepository
     */
    private $pageRepository;

    public function __construct(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }


}