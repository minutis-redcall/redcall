<?php

namespace App\Model\InstancesNationales;

use App\Entity\Volunteer;

class VolunteerExtract
{
    private $id;

    /**
     * @var Volunteer
     */
    private $volunteer;

    private $phoneA = null;
    private $phoneB = null;
    private $emailA = null;
    private $emailB = null;

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

    public function getPhoneB()
    {
        return $this->phoneB;
    }

    public function setPhoneB($phoneB)
    {
        $this->phoneB = $phoneB;

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

    public function getEmailB()
    {
        return $this->emailB;
    }

    public function setEmailB($emailB)
    {
        $this->emailB = $emailB;

        return $this;
    }
}