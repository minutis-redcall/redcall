<?php

namespace App\Manager;

use App\Entity\VolunteerList;
use App\Repository\VolunteerListRepository;

class VolunteerListManager
{
    /**
     * @var VolunteerListRepository
     */
    private $volunteerListRepository;

    public function __construct(VolunteerListRepository $volunteerListRepository)
    {
        $this->volunteerListRepository = $volunteerListRepository;
    }

    public function save(VolunteerList $volunteerList)
    {
        $this->volunteerListRepository->save($volunteerList);
    }

    public function remove(VolunteerList $volunteerList)
    {
        $this->volunteerListRepository->remove($volunteerList);
    }
}