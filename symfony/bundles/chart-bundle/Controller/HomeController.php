<?php

namespace Bundles\ChartBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN")
 * @Route(name="chart_", path="/chart")
 */
class HomeController extends AbstractController
{
    /**
     * @Template
     * @Route(name="home")
     */
    public function index()
    {
        return [

        ];
    }
}