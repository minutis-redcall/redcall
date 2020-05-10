<?php

namespace Bundles\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="developer_webhook_", path="/developer/webhook")
 * @IsGranted("ROLE_DEVELOPER")
 */
class WebhookController extends AbstractController
{

}