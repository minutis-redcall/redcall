<?php

namespace App\Model\Sheets;

class VolunteersExtract
{
    /**
     * @var VolunteerExtract[]
     */
    private $volunteers = [];

    /**
     * @return VolunteersExtract[]
     */
    public function getVolunteers() : array
    {
        return $this->volunteers;
    }

    public function addVolunteer(VolunteerExtract $volunteer) : void
    {
        $this->volunteers[] = $volunteer;
    }
}