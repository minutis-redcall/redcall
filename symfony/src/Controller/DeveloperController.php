<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_DEVELOPER")
 * @Route(name="developer_", path="/developer")
 */
class DeveloperController extends AbstractController
{
    /**
     * @Route(path="/", name="home")
     * @Template()
     */
    public function home()
    {
    }
}