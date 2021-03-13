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
        $platforms = [];
        foreach ($this->parameterBag->get('platforms') as $row) {
            $platform                        = $this->createFromRow($row);
            $platforms[$platform->getName()] = $platform;
        }

        return $platforms;
    }

    public function getPlaform(string $platformName) : PlatformConfig
    {
        $config = $this->parameterBag->get('platforms');
        foreach ($config as $row) {
            if ($platformName === $row['name']) {
                return $this->createFromRow($row);
            }
        }

        throw new \RuntimeException(sprintf('Platform "%s" not found', $platformName));
    }

    public function getLocale(string $platformName) : string
    {
        $platform = $this->getPlaform($platformName);

        return $platform->getDefaultLanguage()->getLocale();
    }

    private function createFromRow(array $row)
    {
        return new PlatformConfig(
            $row['name'],
            $row['label'],
            $row['timezone'],
            $row['flag'],
            $this->languageManager->getLanguageConfig($row['language']),
            $this->phoneManager->getPhoneConfig($row['phone'])
        );
    }
}