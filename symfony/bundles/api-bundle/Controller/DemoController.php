<?php

namespace Bundles\ApiBundle\Controller;

use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Model\Facade\HelloRequestFacade;
use Bundles\ApiBundle\Model\Facade\HelloResponseFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API demos to check if your application successfully signs its requests
 *
 * @IsGranted("ROLE_DEVELOPER")
 * @Route("/api/demo", name="developer_demo_")
 */
class DemoController extends AbstractController
{
    /**
     * Hello, world! (using the query string)
     *
     * All endpoints hit using GET verb take parameters in the query string.
     *
     * @Endpoint(
     *   priority = 5,
     *   request  = @Facade(class = HelloRequestFacade::class),
     *   response = @Facade(class = HelloResponseFacade::class)
     * )
     * @Route(name="hello_get", methods={"GET"})
     */
    public function helloGet(HelloRequestFacade $demo)
    {
        return new HelloResponseFacade(
            $demo->getName()
        );
    }

    /**
     * Hello, world! (using a body)
     *
     * All endpoints hit using POST, PUT and DELETE take parameters in the request payload.
     *
     * @Endpoint(
     *   priority = 5,
     *   request  = @Facade(class = HelloRequestFacade::class),
     *   response = @Facade(class = HelloResponseFacade::class)
     * )
     * @Route(name="hello_post", methods={"POST", "PUT", "DELETE"})
     */
    public function helloPost(HelloRequestFacade $demo)
    {
        return new HelloResponseFacade(
            $demo->getName()
        );
    }
}