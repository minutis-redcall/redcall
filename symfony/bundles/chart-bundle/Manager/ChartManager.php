<?php

namespace Bundles\ChartBundle\Manager;

use Bundles\ChartBundle\Repository\VisualizationRepository;

class ChartManager
{
    /**
     * @var VisualizationRepository
     */
    private $chartRepository;

    public function __construct(VisualizationRepository $chartRepository)
    {
        $this->chartRepository = $chartRepository;
    }


}