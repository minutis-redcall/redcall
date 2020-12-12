<?php

namespace Bundles\ApiBundle\Controller;

use Bundles\ApiBundle\Model\ApiResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_DEVELOPER")
 */
class DemoController extends BaseController
{
    /**
     * @Route(path="/api/demo", name="developer_demo")
     */
    public function index()
    {
        return new ApiResponse([
            'demo' => 'You successfully authenticated!',
        ]);
    }
}