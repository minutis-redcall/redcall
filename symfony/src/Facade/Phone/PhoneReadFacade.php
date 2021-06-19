<?php

namespace App\Facade\Phone;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class PhoneReadFacade extends PhoneFacade
{
    /**
     * The phone's country code (ISO 3166, Alpha 2).
     *
     * @var string
     */
    protected $countryCode;

    /**
     * The phone number's international prefix.
     *
     * @var int
     */
    protected $prefix;

    /**
     * The human-readable phone number that should be displayed to people living in the same country.
     *
     * @var string
     */
    protected $nationalNumber;

    /**
     * The human-readable phone number that should be displayed to people from other countries.
     *
     * @var string
     */
    protected $internationalNumber;

    /**
     * Whether the phone number has been considered as mobile, based on the giggsey/libphonenumber-for-php
     * library. It is useful in order to anticipate whether a phone number is reachable or not by SMS.
     *
     * @var bool
     */
    protected $isMobile;

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = parent::getExample($decorates);

        $facade->countryCode         = 'FR';
        $facade->prefix              = 33;
        $facade->nationalNumber      = '06 12 34 56 78';
        $facade->internationalNumber = '+33 6 12 34 56 78';
        $facade->isMobile            = true;

        return $facade;
    }

    public function getCountryCode() : string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode) : PhoneFacade
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getPrefix() : int
    {
        return $this->prefix;
    }

    public function setPrefix(int $prefix) : PhoneFacade
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getNationalNumber() : string
    {
        return $this->nationalNumber;
    }

    public function setNationalNumber(string $nationalNumber) : PhoneFacade
    {
        $this->nationalNumber = $nationalNumber;

        return $this;
    }

    public function getInternationalNumber() : string
    {
        return $this->internationalNumber;
    }

    public function setInternationalNumber(string $internationalNumber) : PhoneFacade
    {
        $this->internationalNumber = $internationalNumber;

        return $this;
    }

    public function isMobile() : bool
    {
        return $this->isMobile;
    }

    public function setIsMobile(bool $isMobile) : PhoneFacade
    {
        $this->isMobile = $isMobile;

        return $this;
    }
}