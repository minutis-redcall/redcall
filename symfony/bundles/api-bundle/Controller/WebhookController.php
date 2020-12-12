<?php

namespace Bundles\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="developer_webhook_", path="/developer/webhook")
 * @IsGranted("ROLE_DEVELOPER")
 */
class WebhookController extends BaseController
{
    /**
     * @Route(path="/", name="index")
     */
    public function index()
    {
        return $this->render('@Api/webhook/index.html.twig', [

        ]);
    }
}