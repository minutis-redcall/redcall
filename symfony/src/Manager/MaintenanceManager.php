<?php

namespace App\Manager;

use App\Settings;
use App\Task\SyncWithPegassTask;
use Bundles\GoogleTaskBundle\Service\TaskSender;
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
     * @var TaskSender
     */
    private $async;

    public function __construct(KernelInterface $kernel,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        SettingManager $settingManager,
        TaskSender $async)
    {
        $this->kernel           = $kernel;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->settingManager   = $settingManager;
        $this->async            = $async;
    }

    public function refresh()
    {
        $this->async->fire(SyncWithPegassTask::class);

        $this->settingManager->set(Settings::MAINTENANCE_LAST_REFRESH, time());
    }
}