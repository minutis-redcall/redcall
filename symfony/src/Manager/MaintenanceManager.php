<?php

namespace App\Manager;

use Symfony\Component\HttpKernel\KernelInterface;

class MaintenanceManager
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @param KernelInterface  $kernel
     * @param StructureManager $structureManager
     * @param VolunteerManager $volunteerManager
     */
    public function __construct(KernelInterface $kernel,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager)
    {
        $this->kernel           = $kernel;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
    }

    public function refreshAll()
    {
        $this->structureManager->expireAll();
        $this->volunteerManager->expireAll();

        $this->refresh();
    }

    public function refresh()
    {
        // Executing asynchronous task to prevent against interruptions
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s refresh --env=prod', escapeshellarg($console));
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));
    }
}