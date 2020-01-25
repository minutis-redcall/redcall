<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(path="management/volunteers", name="management_volunteers_")
 */
class VolunteersController extends BaseController
{
    /**
     * @Route(name="list")
     */
    public function listAction()
    {
        return $this->render('management/volunteers/list.html.twig');
    }
}
