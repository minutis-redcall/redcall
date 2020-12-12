<?php

namespace Bundles\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_DEVELOPER")
 */
class DemoController extends BaseController
{
    /**
     * @Route(path="/developer/demo", name="developer_demo")
     */
    public function index()
    {
        return new JsonResponse([
            'success' => true,
        ]);
    }
}