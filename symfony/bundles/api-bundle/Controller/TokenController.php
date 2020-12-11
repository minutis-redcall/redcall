<?php

namespace Bundles\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="developer_token_", path="/developer/token")
 * @IsGranted("ROLE_DEVELOPER")
 */
class TokenController extends AbstractController
{
    /**
     * @Route(path="/", name="index")
     */
    public function index()
    {
        return $this->render('@Api/token/index.html.twig', [

        ]);
    }
}