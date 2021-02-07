<?php

namespace App\Model;

class Country
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var bool
     */
    private $outboundCallEnabled;

    /**
     * @var string|null
     */
    private $outboundCallNumber;

    /**
     * @var bool
     */
    private $outboundSmsEnabled;

    /**
     * @var string|null
     */
    private $outboundSmsNumber;

    /**
     * @var bool
     */
    private $inboundCallEnabled;

    /**
     * @var string|null
     */
    private $inboundCallNumber;

    /**
     * @var bool
     */
    private $inboundSmsEnabled;

    public function __construct(string $locale,
        string $timezone,
        bool $outboundCallEnabled,
        ?string $outboundCallNumber,
        bool $outboundSmsEnabled,
        ?string $outboundSmsNumber,
        bool $inboundCallEnabled,
        ?string $inboundCallNumber,
        bool $inboundSmsEnabled)
    {
        $this->locale              = $locale;
        $this->timezone            = $timezone;
        $this->outboundCallEnabled = $outboundCallEnabled;
        $this->outboundCallNumber  = $outboundCallNumber;
        $this->outboundSmsEnabled  = $outboundSmsEnabled;
        $this->outboundSmsNumber   = $outboundSmsNumber;
        $this->inboundCallEnabled  = $inboundCallEnabled;
        $this->inboundCallNumber   = $inboundCallNumber;
        $this->inboundSmsEnabled   = $inboundSmsEnabled;
    }

    public function getLocale() : string
    {
        return $this->locale;
    }

    public function getTimezone() : string
    {
        return $this->timezone;
    }

    public function isOutboundCallEnabled() : bool
    {
        return $this->outboundCallEnabled;
    }

    public function getOutboundCallNumber() : ?string
    {
        return $this->outboundCallNumber;
    }

    public function isOutboundSmsEnabled() : bool
    {
        return $this->outboundSmsEnabled;
    }

    public function getOutboundSmsNumber() : ?string
    {
        return $this->outboundSmsNumber;
    }

    public function isInboundCallEnabled() : bool
    {
        return $this->inboundCallEnabled;
    }

    public function getInboundCallNumber() : ?string
    {
        return $this->inboundCallNumber;
    }

    public function isInboundSmsEnabled() : bool
    {
        return $this->inboundSmsEnabled;
    }
}