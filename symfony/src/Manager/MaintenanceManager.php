<?php

namespace App\Manager;

use App\Settings;
use App\Task\PegassCreateChunks;
use App\Task\SyncWithPegassTask;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Bundles\SettingsBundle\Manager\SettingManager;

class MaintenanceManager
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var TaskSender
     */
    private $async;

    public function __construct(SettingManager $settingManager, TaskSender $async)
    {
        $this->settingManager = $settingManager;
        $this->async          = $async;
    }

    public function refresh()
    {
        $this->async->fire(SyncWithPegassTask::class);

        $this->settingManager->set(Settings::MAINTENANCE_LAST_REFRESH, time());
    }

    public function pegassFiles()
    {
        $this->async->fire(PegassCreateChunks::class);
    }
}