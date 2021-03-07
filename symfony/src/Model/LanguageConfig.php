<?php

namespace App\Model;

class LanguageConfig
{
    /**
     * @var string
     */
    private $localizedName;

    /**
     * @var string
     */
    private $englishName;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $brand;

    /**
     * @var TextToSpeechConfig
     */
    private $textToSpeech;

    public function __construct(string $localizedName,
        string $englishName,
        string $locale,
        string $brand,
        TextToSpeechConfig $textToSpeech)
    {
        $this->localizedName = $localizedName;
        $this->englishName   = $englishName;
        $this->locale        = $locale;
        $this->brand         = $brand;
        $this->textToSpeech  = $textToSpeech;
    }

    public function getLocalizedName() : string
    {
        return $this->localizedName;
    }

    public function getEnglishName() : string
    {
        return $this->englishName;
    }

    public function getLocale() : string
    {
        return $this->locale;
    }

    public function getBrand() : string
    {
        return $this->brand;
    }

    public function getTextToSpeech() : TextToSpeechConfig
    {
        return $this->textToSpeech;
    }
}