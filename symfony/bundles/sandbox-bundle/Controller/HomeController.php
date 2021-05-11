<?php

namespace Bundles\SandboxBundle\Controller;

use Bundles\SandboxBundle\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends BaseController
{
    /**
     * @Route(name="home")
     * @Template()
     */
    public function indexAction()
    {
    }
}
