<?php

namespace App\Manager;

use App\Entity\Communication;
use App\Model\LanguageConfig;
use App\Model\TextToSpeechConfig;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LanguageConfigManager
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return LanguageConfig[]
     */
    public function getAvailableLanguages() : array
    {
        return array_map(function (array $row) {
            return $this->createLanguageObject($row);
        }, array_change_key_case($this->parameterBag->get('languages'), CASE_LOWER));
    }

    public function getAvailableLanguageChoices() : array
    {
        $available = [];
        foreach ($this->getAvailableLanguages() as $language) {
            $available[$language->getLocalizedName()] = $language->getLocale();
        }

        return $available;
    }

    public function getLanguageConfig(Communication $communication) : ?LanguageConfig
    {
        $languages = array_change_key_case($this->parameterBag->get('languages'), CASE_LOWER);

        $language = $languages[strtolower($communication->getLanguage())] ?? null;
        if (!$language) {
            return null;
        }

        return $this->createLanguageObject($language);
    }

    private function createLanguageObject(array $row) : LanguageConfig
    {
        return new LanguageConfig(
            $row['localized_name'],
            $row['english_name'],
            $row['locale'],
            $row['brand'],
            new TextToSpeechConfig(
                $row['text_to_speech']['language_code'],
                $row['text_to_speech']['male_voice'],
                $row['text_to_speech']['female_voice']
            )
        );
    }
}