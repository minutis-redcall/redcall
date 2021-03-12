<?php

namespace App\Manager;

use App\Model\PlatformConfig;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PlatformConfigManager
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var LanguageConfigManager
     */
    private $languageManager;

    /**
     * @var PhoneConfigManager
     */
    private $phoneManager;

    public function __construct(ParameterBagInterface $parameterBag,
        LanguageConfigManager $languageManager,
        PhoneConfigManager $phoneManager)
    {
        $this->parameterBag    = $parameterBag;
        $this->languageManager = $languageManager;
        $this->phoneManager    = $phoneManager;
    }

    /**
     * @return PlatformConfig[]
     */
    public function getAvailablePlatforms() : array
    {
        return array_map(function (array $row) {
            return new PlatformConfig(
                $row['name'],
                $row['label'],
                $this->languageManager->getLanguageConfig($row['language']),
                $this->phoneManager->getPhoneConfig($row['phone'])
            );
        }, $this->parameterBag->get('platforms'));
    }
}