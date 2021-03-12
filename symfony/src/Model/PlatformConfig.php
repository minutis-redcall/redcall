<?php

namespace App\Model;

class PlatformConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $label;

    /**
     * @var LanguageConfig
     */
    private $defaultLanguage;

    /**
     * @var PhoneConfig
     */
    private $defaultPhone;

    public function __construct(string $name, string $label, LanguageConfig $defaultLanguage, PhoneConfig $defaultPhone)
    {
        $this->name            = $name;
        $this->label           = $label;
        $this->defaultLanguage = $defaultLanguage;
        $this->defaultPhone    = $defaultPhone;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function getDefaultLanguage() : LanguageConfig
    {
        return $this->defaultLanguage;
    }

    public function getDefaultPhone() : PhoneConfig
    {
        return $this->defaultPhone;
    }
}