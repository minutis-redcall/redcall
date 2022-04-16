<?php

namespace App\Model;

use App\Entity\Volunteer;

class PhoneConfig
{
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
    private $outboundSmsShort;

    /**
     * @var string|null
     */
    private $outboundSmsLong;

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

    public function __construct(string $timezone,
        bool $outboundCallEnabled,
        ?string $outboundCallNumber,
        bool $outboundSmsEnabled,
        ?string $outboundSmsShort,
        ?string $outboundSmsLong,
        bool $inboundCallEnabled,
        ?string $inboundCallNumber,
        bool $inboundSmsEnabled)
    {
        $this->timezone            = $timezone;
        $this->outboundCallEnabled = $outboundCallEnabled;
        $this->outboundCallNumber  = $outboundCallNumber;
        $this->outboundSmsEnabled  = $outboundSmsEnabled;
        $this->outboundSmsShort    = $outboundSmsShort;
        $this->outboundSmsLong     = $outboundSmsLong;
        $this->inboundCallEnabled  = $inboundCallEnabled;
        $this->inboundCallNumber   = $inboundCallNumber;
        $this->inboundSmsEnabled   = $inboundSmsEnabled;
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

    public function getOutboundSmsShort() : ?string
    {
        return $this->outboundSmsShort;
    }

    public function getOutboundSmsLong() : ?string
    {
        return $this->outboundSmsLong;
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

    public function getOutboutSmsSenderByVolunteer(Volunteer $volunteer) : string
    {
        if ($this->getOutboundSmsShort()) {
            $sender = $this->getOutboundSmsShort();
            if (!$volunteer->isSupportsShortCode() && $this->getOutboundSmsLong()) {
                $sender = $this->getOutboundSmsLong();
            }
        } else {
            $sender = $this->getOutboundSmsLong();
        }

        return $sender;
    }
}