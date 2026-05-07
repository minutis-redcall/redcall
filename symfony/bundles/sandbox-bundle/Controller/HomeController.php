<?php

namespace Bundles\SandboxBundle\Controller;

use Bundles\SandboxBundle\Base\BaseController;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends BaseController
{
    #[Route(name: "home")]
    #[Template("@Sandbox/home/index.html.twig")]
    public function indexAction()
    {
    }
}
