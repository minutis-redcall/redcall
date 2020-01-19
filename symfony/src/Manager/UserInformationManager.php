<?php

namespace App\Manager;

use App\Entity\UserInformation;
use App\Repository\UserInformationRepository;
use Bundles\PasswordLoginBundle\Entity\User;

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
     * @param User $user
     *
     * @return UserInformation|null
     */
    public function findOneByUser(User $user): ?UserInformation
    {
        return $this->userInformationRepository->findOneByUser($user);
    }

    /**
     * @param UserInformation $userInformation
     */
    public function save(UserInformation $userInformation)
    {
        $this->userInformationRepository->save($userInformation);
    }
}