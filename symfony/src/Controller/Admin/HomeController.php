<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\StructureManager;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="admin/", name="admin_")
 */
class HomeController extends BaseController
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @param StructureManager $structureManager
     */
    public function __construct(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * @Route(name="home")
     */
    public function indexAction()
    {
        $this->structureManager->createRedCallStructure();

        return $this->render('admin/home.html.twig');
    }
}
