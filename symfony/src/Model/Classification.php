<?php

namespace App\Model;

class Classification
{
    const LARGE_AUDIENCE = 250;

    private $invalid        = [];
    private $disabled       = [];
    private $inaccessible   = [];
    private $excluded       = [];
    private $phoneMissing   = [];
    private $phoneOptout    = [];
    private $phoneLandline  = [];
    private $optoutUntil    = [];
    private $emailMissing   = [];
    private $emailOptout    = [];
    private $excludedMinors = [];
    private $reachable      = [];

    public function getInvalid() : array
    {
        return $this->invalid;
    }

    public function setInvalid(array $invalid) : void
    {
        $this->invalid = $invalid;
    }

    public function getDisabled() : array
    {
        return $this->disabled;
    }

    public function setDisabled(array $disabled) : void
    {
        $this->disabled = $disabled;
    }

    public function getInaccessible() : array
    {
        return $this->inaccessible;
    }

    public function setInaccessible(array $inaccessible) : void
    {
        $this->inaccessible = $inaccessible;
    }

    public function getExcluded() : array
    {
        return $this->excluded;
    }

    public function setExcluded(array $excluded) : void
    {
        $this->excluded = $excluded;
    }

    public function getPhoneMissing() : array
    {
        return $this->phoneMissing;
    }

    public function setPhoneMissing(array $phoneMissing) : void
    {
        $this->phoneMissing = $phoneMissing;
    }

    public function getPhoneOptout() : array
    {
        return $this->phoneOptout;
    }

    public function setPhoneOptout(array $phoneOptout) : void
    {
        $this->phoneOptout = $phoneOptout;
    }

    public function getPhoneLandline() : array
    {
        return $this->phoneLandline;
    }

    public function setPhoneLandline(array $phoneLandline) : void
    {
        $this->phoneLandline = $phoneLandline;
    }

    public function getOptoutUntil() : array
    {
        return $this->optoutUntil;
    }

    public function setOptoutUntil(array $optoutUntil) : Classification
    {
        $this->optoutUntil = $optoutUntil;

        return $this;
    }

    public function getEmailMissing() : array
    {
        return $this->emailMissing;
    }

    public function setEmailMissing(array $emailMissing) : void
    {
        $this->emailMissing = $emailMissing;
    }

    public function getEmailOptout() : array
    {
        return $this->emailOptout;
    }

    public function setEmailOptout(array $emailOptout) : void
    {
        $this->emailOptout = $emailOptout;
    }

    public function getExcludedMinors() : array
    {
        return $this->excludedMinors;
    }

    public function setExcludedMinors(array $excludedMinors) : self
    {
        $this->excludedMinors = $excludedMinors;

        return $this;
    }

    public function getReachable() : array
    {
        return $this->reachable;
    }

    public function setReachable(array $reachable) : void
    {
        $this->reachable = $reachable;
    }

    public function hasProblems() : bool
    {
        foreach ($this->toArray() as $key => $value) {
            if ('reachable' !== $key && $value) {
                return true;
            }
        }

        return false;
    }

    public function toArray() : array
    {
        return get_object_vars($this);
    }

    public function getKeys() : array
    {
        return array_keys(get_class_vars(__CLASS__));
    }
}
