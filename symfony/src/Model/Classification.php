<?php

namespace App\Model;

class Classification
{
    private $invalid       = [];
    private $disabled      = [];
    private $inaccessible  = [];
    private $phoneMissing  = [];
    private $phoneOptout   = [];
    private $phoneLandline = [];
    private $emailMissing  = [];
    private $emailOptout   = [];
    private $reachable     = [];

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
        return array_keys(get_class_vars(self));
    }
}
