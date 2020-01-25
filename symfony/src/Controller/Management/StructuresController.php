<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(path="management/structures", name="management_structures_")
 */
class StructuresController extends BaseController
{
    /**
     * @Route(name="list")
     */
    public function listAction()
    {
        return $this->render('management/structures/list.html.twig');
    }
}
