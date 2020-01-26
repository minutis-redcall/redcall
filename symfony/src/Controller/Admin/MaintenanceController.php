<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\MaintenanceManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(path="admin/maintenance/", name="admin_maintenance_")
 */
class MaintenanceController extends BaseController
{
    /**
     * @var MaintenanceManager
     */
    private $maintenanceManager;

    /**
     * @param MaintenanceManager $maintenanceManager
     */
    public function __construct(MaintenanceManager $maintenanceManager)
    {
        $this->maintenanceManager = $maintenanceManager;
    }

    /**
     * @Route(name="index")
     */
    public function index()
    {
        return $this->render('admin/maintenance/index.html.twig');
    }

    /**
     * @Route(name="refresh", path="/refresh")
     */
    public function refresh()
    {
        if ($this->maintenanceManager->refresh()) {
            $this->success('maintenance.refresh_started');
        } else {
            $this->alert('maintenance.refresh_error');
        }

        return $this->redirectToRoute('admin_maintenance_index');
    }

    /**
     * @Route(name="refresh_all", path="/refresh-all")
     */
    public function refreshAll()
    {
        if ($this->maintenanceManager->refreshAll()) {
            $this->success('maintenance.refresh_started');
        } else {
            $this->alert('maintenance.refresh_error');
        }

        return $this->redirectToRoute('admin_maintenance_index');
    }
}
