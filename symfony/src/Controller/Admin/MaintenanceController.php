<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @Route(path="admin/maintenance/", name="admin_maintenance_")
 */
class MaintenanceController extends BaseController
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
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
        // Executing asynchronous task to prevent against interruptions
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s refresh --env=prod', escapeshellarg($console));
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));

        $this->success('maintenance.refresh_started');

        return $this->redirectToRoute('admin_maintenance_index');
    }
}
