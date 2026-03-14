<?php

namespace App\Tests\Manager;

use App\Entity\Communication;
use App\Manager\LanguageConfigManager;
use App\Model\LanguageConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LanguageConfigManagerTest extends KernelTestCase
{
    private LanguageConfigManager $manager;

    protected function setUp() : void
    {
        self::bootKernel();
        $this->manager = static::getContainer()->get(LanguageConfigManager::class);
    }

    public function testGetAvailableLanguagesReturnsAllConfiguredLanguages()
    {
        $languages = $this->manager->getAvailableLanguages();

        $this->assertIsArray($languages);
        $this->assertCount(2, $languages);

        foreach ($languages as $language) {
            $this->assertInstanceOf(LanguageConfig::class, $language);
        }

        $locales = array_map(function (LanguageConfig $l) {
            return $l->getLocale();
        }, $languages);

        $this->assertContains('fr', $locales);
        $this->assertContains('en', $locales);
    }

    public function testGetAvailableLanguagesReturnsFrenchConfig()
    {
        $languages = $this->manager->getAvailableLanguages();
        $french = null;
        foreach ($languages as $language) {
            if ($language->getLocale() === 'fr') {
                $french = $language;
                break;
            }
        }

        $this->assertNotNull($french);
        $this->assertSame('Français', $french->getLocalizedName());
        $this->assertSame('French', $french->getEnglishName());
        $this->assertSame('Croix-Rouge', $french->getBrand());
        $this->assertSame('fr-FR', $french->getTextToSpeech()->getLanguageCode());
        $this->assertSame('fr-FR-Wavenet-D', $french->getTextToSpeech()->getMaleVoice());
        $this->assertSame('fr-FR-Wavenet-E', $french->getTextToSpeech()->getFemaleVoice());
    }

    public function testGetAvailableLanguagesReturnsEnglishConfig()
    {
        $languages = $this->manager->getAvailableLanguages();
        $english = null;
        foreach ($languages as $language) {
            if ($language->getLocale() === 'en') {
                $english = $language;
                break;
            }
        }

        $this->assertNotNull($english);
        $this->assertSame('English', $english->getLocalizedName());
        $this->assertSame('English', $english->getEnglishName());
        $this->assertSame('Red-Cross', $english->getBrand());
        $this->assertSame('en-US', $english->getTextToSpeech()->getLanguageCode());
    }

    public function testGetAvailableLanguageChoicesReturnsLocalizedNameToLocaleMap()
    {
        $choices = $this->manager->getAvailableLanguageChoices();

        $this->assertIsArray($choices);
        $this->assertCount(2, $choices);
        $this->assertArrayHasKey('Français', $choices);
        $this->assertArrayHasKey('English', $choices);
        $this->assertSame('fr', $choices['Français']);
        $this->assertSame('en', $choices['English']);
    }

    public function testGetLanguageConfigForCommunication()
    {
        $communication = new Communication();
        $communication->setLanguage('fr');

        $config = $this->manager->getLanguageConfigForCommunication($communication);

        $this->assertNotNull($config);
        $this->assertInstanceOf(LanguageConfig::class, $config);
        $this->assertSame('fr', $config->getLocale());
        $this->assertSame('Français', $config->getLocalizedName());
    }

    public function testGetLanguageConfigForCommunicationReturnsNullForUnknownLanguage()
    {
        $communication = new Communication();
        $communication->setLanguage('xx');

        $config = $this->manager->getLanguageConfigForCommunication($communication);

        $this->assertNull($config);
    }

    public function testGetLanguageConfigReturnsConfigForValidLocale()
    {
        $config = $this->manager->getLanguageConfig('fr');

        $this->assertNotNull($config);
        $this->assertInstanceOf(LanguageConfig::class, $config);
        $this->assertSame('fr', $config->getLocale());
    }

    public function testGetLanguageConfigIsCaseInsensitive()
    {
        $config = $this->manager->getLanguageConfig('FR');

        $this->assertNotNull($config);
        $this->assertSame('fr', $config->getLocale());
    }

    public function testGetLanguageConfigReturnsNullForUnknownLocale()
    {
        $config = $this->manager->getLanguageConfig('zz');

        $this->assertNull($config);
    }

    public function testGetLanguageConfigForEnglish()
    {
        $config = $this->manager->getLanguageConfig('en');

        $this->assertNotNull($config);
        $this->assertSame('en', $config->getLocale());
        $this->assertSame('Red-Cross', $config->getBrand());
    }
}
