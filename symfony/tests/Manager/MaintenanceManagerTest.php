<?php

namespace App\Tests\Manager;

use App\Manager\MaintenanceManager;
use App\Settings;
use Bundles\SettingsBundle\Manager\SettingManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MaintenanceManagerTest extends KernelTestCase
{
    private MaintenanceManager $manager;
    private SettingManager $settingManager;

    protected function setUp() : void
    {
        self::bootKernel();

        $container            = static::getContainer();
        $this->manager        = $container->get(MaintenanceManager::class);
        $this->settingManager = $container->get(SettingManager::class);
    }

    public function testDataSyncUpdatesLastRefreshTimestamp()
    {
        $before = time();

        $this->manager->dataSync();

        $after = time();

        $lastRefresh = (int) $this->settingManager->get(Settings::MAINTENANCE_LAST_REFRESH);

        $this->assertGreaterThanOrEqual($before, $lastRefresh);
        $this->assertLessThanOrEqual($after, $lastRefresh);
    }

    public function testDataSyncOverwritesPreviousTimestamp()
    {
        // Set a known value
        $this->settingManager->set(Settings::MAINTENANCE_LAST_REFRESH, 1000);

        $before = time();
        $this->manager->dataSync();

        $lastRefresh = (int) $this->settingManager->get(Settings::MAINTENANCE_LAST_REFRESH);

        // Should be updated to current time, not 1000
        $this->assertGreaterThanOrEqual($before, $lastRefresh);
    }
}
