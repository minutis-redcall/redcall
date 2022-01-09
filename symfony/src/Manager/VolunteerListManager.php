<?php

namespace App\Manager;

use App\Entity\VolunteerList;
use App\Repository\VolunteerListRepository;
use App\Security\Helper\Security;

class VolunteerListManager
{
    /**
     * @var VolunteerListRepository
     */
    private $volunteerListRepository;

    /**
     * @var Security
     */
    private $security;

    public function __construct(VolunteerListRepository $volunteerListRepository, Security $security)
    {
        $this->volunteerListRepository = $volunteerListRepository;
        $this->security                = $security;
    }

    public function save(VolunteerList $volunteerList)
    {
        $this->volunteerListRepository->save($volunteerList);
    }

    public function remove(VolunteerList $volunteerList)
    {
        $this->volunteerListRepository->remove($volunteerList);
    }

    /**
     * @return VolunteerList[]
     */
    public function getVolunteerListsForCurrentUser() : array
    {
        return $this->volunteerListRepository->findVolunteerListsForUser(
            $this->security->getPlatform(),
            $this->security->getUser()
        );
    }
}