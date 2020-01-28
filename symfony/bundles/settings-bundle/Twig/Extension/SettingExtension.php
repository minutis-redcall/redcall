<?php

namespace Bundles\SettingsBundle\Twig\Extension;

use Bundles\SettingsBundle\Manager\SettingManager;
use Twig\Extension\AbstractExtension;

class SettingExtension extends AbstractExtension
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @param SettingManager $settingManager
     */
    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('setting', [$this, 'setting']),
        ];
    }

    /**
     * @param string      $property
     * @param string|null $default
     *
     * @return string|null
     */
    public function setting(string $property, ?string $default = null): ?string
    {
        if (isset($this->settings[$property])) {
            return $this->settings[$property];
        }

        $value = $this->settingManager->get($property, $default);

        $this->settings[$property] = $value;

        return $value;
    }
}