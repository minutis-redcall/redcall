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
     * @var string
     */
    private $timezone;

    /**
     * @var string
     */
    private $flag;

    /**
     * @var LanguageConfig
     */
    private $defaultLanguage;

    /**
     * @var PhoneConfig
     */
    private $defaultPhone;

    public function __construct(string $name,
        string $label,
        string $timezone,
        string $flag,
        LanguageConfig $defaultLanguage,
        PhoneConfig $defaultPhone)
    {
        $this->name            = $name;
        $this->label           = $label;
        $this->timezone        = $timezone;
        $this->flag            = $flag;
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

    public function getTimezone() : string
    {
        return $this->timezone;
    }

    public function getFlag() : string
    {
        return $this->flag;
    }

    public function getDefaultLanguage() : LanguageConfig
    {
        return $this->defaultLanguage;
    }

    public function getDefaultPhone() : PhoneConfig
    {
        return $this->defaultPhone;
    }

    public function __toString() : string
    {
        return $this->name;
    }
}