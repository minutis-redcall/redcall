<?php

namespace App\Model\Sheets;

use App\Entity\Volunteer;

class VolunteerExtract
{
    private $id;

    /**
     * @var Volunteer
     */
    private $volunteer;

    private $phoneA          = null;
    private $volunteerPhoneA = null;

    private $phoneB          = null;
    private $volunteerPhoneB = null;

    private $emailA          = null;
    private $volunteerEmailA = null;

    private $emailB          = null;
    private $volunteerEmailB = null;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    public function getPhoneA()
    {
        return $this->phoneA;
    }

    public function setPhoneA($phoneA)
    {
        $this->phoneA = $phoneA;

        return $this;
    }

    public function getVolunteerPhoneA()
    {
        return $this->volunteerPhoneA;
    }

    public function setVolunteerPhoneA($volunteerPhoneA)
    {
        $this->volunteerPhoneA = $volunteerPhoneA;

        return $this;
    }

    public function getPhoneB()
    {
        return $this->phoneB;
    }

    public function setPhoneB($phoneB)
    {
        $this->phoneB = $phoneB;

        return $this;
    }

    public function getVolunteerPhoneB()
    {
        return $this->volunteerPhoneB;
    }

    public function setVolunteerPhoneB($volunteerPhoneB)
    {
        $this->volunteerPhoneB = $volunteerPhoneB;

        return $this;
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

    public function getVolunteerEmailA()
    {
        return $this->volunteerEmailA;
    }

    public function setVolunteerEmailA($volunteerEmailA)
    {
        $this->volunteerEmailA = $volunteerEmailA;

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

    public function getVolunteerEmailB()
    {
        return $this->volunteerEmailB;
    }

    public function setVolunteerEmailB($volunteerEmailB)
    {
        $this->volunteerEmailB = $volunteerEmailB;

        return $this;
    }
}