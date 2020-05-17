<?php

namespace App\Controller;

use App\Manager\MessageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="deploy")
 */
class DeployController extends AbstractController
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @param MessageManager $messageManager
     */
    public function __construct(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * @Route()
     */
    public function check()
    {
        return new Response(
            $this->messageManager->getDeployGreenlight()
        );
    }
}
