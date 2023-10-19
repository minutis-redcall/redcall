<?php

namespace App\Model\InstancesNationales;

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

    public function getVolunteer(string $id) : ?VolunteerExtract
    {
        return $this->volunteers[$id] ?? null;
    }

    public function addVolunteer(VolunteerExtract $volunteer) : void
    {
        $this->volunteers[$volunteer->getId()] = $volunteer;
    }

    public function count() : int
    {
        return count($this->volunteers);
    }

    public function remove(VolunteerExtract $volunteer) : void
    {
        if (isset($this->volunteers[$volunteer->getId()])) {
            unset($this->volunteers[$volunteer->getId()]);
        }
    }

    public function getIds() : array
    {
        return array_keys($this->volunteers);
    }
}