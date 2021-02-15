<?php

namespace Bundles\ApiBundle\Controller;

use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Model\Facade\DemoFacade;
use Bundles\ApiBundle\Model\Facade\HelloRequestFacade;
use Bundles\ApiBundle\Model\Facade\HelloResponseFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API demos to understand the different request and response types.
 *
 * @IsGranted("ROLE_DEVELOPER")
 * @Route("/api/demo", name="developer_demo_")
 */
class DemoController extends AbstractController
{
    /**
     * Hello, world!
     *
     * This endpoint aims to check if your application successfully signs its requests.
     *
     * @Endpoint(
     *   priority = 5,
     *   request  = @Facade(class = HelloRequestFacade::class),
     *   response = @Facade(class = HelloResponseFacade::class)
     * )
     * @Route(path="/api/demo", name="hello")
     */
    public function hello(HelloRequestFacade $demo)
    {
        return new HelloResponseFacade(
            $demo->getName()
        );
    }
}