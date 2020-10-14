<?php

namespace App\Manager;

use App\Settings;
use Bundles\SettingsBundle\Manager\SettingManager;
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
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @param KernelInterface  $kernel
     * @param StructureManager $structureManager
     * @param VolunteerManager $volunteerManager
     * @param SettingManager   $settingManager
     */
    public function __construct(KernelInterface $kernel,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        SettingManager $settingManager)
    {
        $this->kernel           = $kernel;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->settingManager   = $settingManager;
    }

    public function refreshAll()
    {
        $this->refresh(true);
    }

    public function refresh($force = false)
    {
        $force = $force ? '--force' : '';

        $this->settingManager->set(Settings::MAINTENANCE_LAST_REFRESH, time());

        // Executing asynchronous task to prevent against interruptions
        $console = sprintf('%s/../bin/console', $this->kernel->getRootDir());
        $command = sprintf('%s refresh %s', escapeshellarg($console), $force);
        exec(sprintf('%s > /dev/null 2>&1 & echo -n \$!', $command));
    }
}