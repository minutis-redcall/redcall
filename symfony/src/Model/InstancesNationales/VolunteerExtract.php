<?php

namespace App\Model\InstancesNationales;

use App\Entity\Phone;
use App\Entity\Volunteer;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class VolunteerExtract
{
    const NIVOL_PREFIX    = 'annuaire-';
    const PREFERRED_EMAIL = '/@croix-rouge\.fr$/i';

    private $id;

    /**
     * @var Volunteer
     */
    private $volunteer;

    private $firstname = null;
    private $lastname  = null;
    private $phoneA    = null;
    private $phoneB    = null;
    private $emailA    = null;
    private $emailB    = null;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getNivol() : string
    {
        return self::buildNivol($this->id);
    }

    static public function buildNivol(string $id) : string
    {
        return self::NIVOL_PREFIX.$id;
    }

    public function getVolunteer() : Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(Volunteer $volunteer) : self
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhone() : ?string
    {
        if ($this->isMobile($e164 = $this->getPhoneA())) {
            return $e164;
        }

        if ($this->isMobile($e164 = $this->getPhoneB())) {
            return $e164;
        }

        return null;
    }

    private function isMobile(?string $phoneNumber) : bool
    {
        if (null === $phoneNumber) {
            return false;
        }

        /** @var PhoneNumber $parsed */
        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsed    = $phoneUtil->parse($phoneNumber, Phone::DEFAULT_LANG);
        $e164      = $phoneUtil->format($parsed, PhoneNumberFormat::E164);

        $phone = new Phone();
        $phone->setE164($e164);
        $phone->onChange();

        return $phone->isMobile();
    }

    public function getPhoneA() : ?string
    {
        return $this->phoneA;
    }

    public function setPhoneA(?string $phoneA) : self
    {
        $this->phoneA = $phoneA;

        return $this;
    }

    public function getPhoneB() : ?string
    {
        return $this->phoneB;
    }

    public function setPhoneB($phoneB) : self
    {
        $this->phoneB = $phoneB;

        return $this;
    }

    public function getEmail()
    {
        if (null !== $this->getEmailA() && preg_match(self::PREFERRED_EMAIL, $this->getEmailA())) {
            return $this->getEmailA();
        }

        if (null !== $this->getEmailB() && preg_match(self::PREFERRED_EMAIL, $this->getEmailB())) {
            return $this->getEmailB();
        }

        if (null !== $this->getEmailA()) {
            return $this->getEmailA();
        }

        if (null !== $this->getEmailB()) {
            return $this->getEmailB();
        }

        return null;
    }

    public function getEmailA()
    {
        return $this->emailA;
    }

    public function setEmailA($emailA)
    {
        $this->emailA = $emailA;

        return $this;
    }

    public function getEmailB()
    {
        return $this->emailB;
    }

    public function setEmailB($emailB)
    {
        $this->emailB = $emailB;

        return $this;
    }

    public function isEmpty() : bool
    {
        return empty($this->phoneA) && empty($this->phoneB) && empty($this->emailA) && empty($this->emailB);
    }
}