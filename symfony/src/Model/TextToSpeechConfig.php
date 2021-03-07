<?php

namespace App\Model;

class TextToSpeechConfig
{
    /**
     * @var string
     */
    private $languageCode;

    /**
     * @var string
     */
    private $maleVoice;

    /**
     * @var string
     */
    private $femaleVoice;

    public function __construct(string $languageCode, string $maleVoice, string $femaleVoice)
    {
        $this->languageCode = $languageCode;
        $this->maleVoice    = $maleVoice;
        $this->femaleVoice  = $femaleVoice;
    }

    public function getLanguageCode() : string
    {
        return $this->languageCode;
    }

    public function getMaleVoice() : string
    {
        return $this->maleVoice;
    }

    public function getFemaleVoice() : string
    {
        return $this->femaleVoice;
    }

    public function getVoice(bool $male) : string
    {
        return $male ? $this->getMaleVoice() : $this->getFemaleVoice();
    }
}