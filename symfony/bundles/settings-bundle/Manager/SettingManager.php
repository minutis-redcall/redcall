<?php

namespace Bundles\SettingsBundle\Manager;

use Bundles\SettingsBundle\Repository\SettingRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class SettingManager
{
    /**
     * @var SettingRepository
     */
    private $settingRepository;

    /**
     * @param SettingRepository $settingRepository
     */
    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->settingRepository->all();
    }

    /**
     * @param string      $property
     * @param string|null $default
     *
     * @return string|null
     */
    public function get(string $property, ?string $default = null): ?string
    {
        return $this->settingRepository->get($property, $default);
    }

    /**
     * @param string $property
     * @param string $value
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function set(string $property, string $value)
    {
        $this->settingRepository->set($property, $value);
    }

    /**
     * @param string $property
     */
    public function remove(string $property)
    {
        $this->settingRepository->remove($property);
    }
}