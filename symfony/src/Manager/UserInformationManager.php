<?php

namespace App\Manager;

use App\Entity\UserInformation;
use App\Repository\UserInformationRepository;

class UserInformationManager
{
    /**
     * @var UserInformationRepository
     */
    private $userInformationRepository;

    /**
     * @param UserInformationRepository $userInformationRepository
     */
    public function __construct(UserInformationRepository $userInformationRepository)
    {
        $this->userInformationRepository = $userInformationRepository;
    }

    /**
     * @return UserInformation[]
     */
    public function findAll(): array
    {
        return $this->userInformationRepository->findAll();
    }

    /**
     * @param UserInformation $userInformation
     */
    public function save(UserInformation $userInformation)
    {
        $this->userInformationRepository->save($userInformation);
    }
}