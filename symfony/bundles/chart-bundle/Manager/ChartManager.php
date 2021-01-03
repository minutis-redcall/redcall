<?php

namespace Bundles\ChartBundle\Manager;

use Bundles\ChartBundle\Repository\ChartRepository;

class ChartManager
{
    /**
     * @var ChartRepository
     */
    private $chartRepository;

    public function __construct(ChartRepository $chartRepository)
    {
        $this->chartRepository = $chartRepository;
    }


}