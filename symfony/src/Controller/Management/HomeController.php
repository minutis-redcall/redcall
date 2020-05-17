<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(path="management/", name="management_")
 */
class HomeController extends BaseController
{
    /**
     * @Route(name="home")
     */
    public function indexAction()
    {
        return $this->render('management/home.html.twig', [
            'email' => getenv('MINUTIS_SUPPORT'),
        ]);
    }
}
