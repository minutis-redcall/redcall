<?php

namespace App\Manager;

use App\Settings;
use App\Task\StartDataSyncTask;
use App\Task\SyncAnnuaire;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Bundles\SettingsBundle\Manager\SettingManager;

class MaintenanceManager
{
    private SettingManager $settingManager;
    private TaskSender $async;

    public function __construct(SettingManager $settingManager, TaskSender $async)
    {
        $this->settingManager = $settingManager;
        $this->async          = $async;
    }

    public function dataSync()
    {
        $this->async->fire(StartDataSyncTask::class, [
            'syncedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        $this->settingManager->set(Settings::MAINTENANCE_LAST_REFRESH, time());
    }

    public function annuaireNational()
    {
        $this->async->fire(SyncAnnuaire::class);
    }
}
