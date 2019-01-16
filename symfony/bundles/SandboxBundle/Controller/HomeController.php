<?php

namespace Bundles\SandboxBundle\Controller;

use App\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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