<?php

namespace App\Model;

class PlatformConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var LanguageConfig
     */
    private $defaultLanguage;

    /**
     * @var PhoneConfig
     */
    private $defaultPhone;

    public function __construct(string $name, LanguageConfig $defaultLanguage, PhoneConfig $defaultPhone)
    {
        $this->name            = $name;
        $this->defaultLanguage = $defaultLanguage;
        $this->defaultPhone    = $defaultPhone;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : PlatformConfig
    {
        $this->name = $name;

        return $this;
    }

    public function getDefaultLanguage() : LanguageConfig
    {
        return $this->defaultLanguage;
    }

    public function setDefaultLanguage(LanguageConfig $defaultLanguage) : PlatformConfig
    {
        $this->defaultLanguage = $defaultLanguage;

        return $this;
    }

    public function getDefaultPhone() : PhoneConfig
    {
        return $this->defaultPhone;
    }

    public function setDefaultPhone(PhoneConfig $defaultPhone) : PlatformConfig
    {
        $this->defaultPhone = $defaultPhone;

        return $this;
    }
}