<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="admin/", name="admin_")
 */
class HomeController extends BaseController
{
    /**
     * @Route(name="home")
     */
    public function indexAction()
    {
        return $this->render('admin/home.html.twig');
    }
}
