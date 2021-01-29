<?php

namespace Bundles\ApiBundle\Controller;

use Bundles\ApiBundle\Model\Facade\DemoFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_DEVELOPER")
 */
class DemoController extends AbstractController
{
    /**
     * @Route(path="/api/demo", name="developer_demo")
     */
    public function index(DemoFacade $demo)
    {
        return $demo;
    }
}