<?php

namespace App\Manager;

use App\Entity\UserInformation;
use App\Repository\UserInformationRepository;
use Bundles\PasswordLoginBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserInformationManager
{
    /**
     * @var UserInformationRepository
     */
    private $userInformationRepository;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param UserInformationRepository $userInformationRepository
     * @param TokenStorageInterface     $tokenStorage
     */
    public function __construct(UserInformationRepository $userInformationRepository,
        TokenStorageInterface $tokenStorage)
    {
        $this->userInformationRepository = $userInformationRepository;
        $this->tokenStorage              = $tokenStorage;
    }

    /**
     * @return UserInformation[]
     */
    public function findAll(): array
    {
        return $this->userInformationRepository->findAll();
    }

    /**
     * @return UserInformation|null
     */
    public function findForCurrentUser(): ?UserInformation
    {
        return $this->findOneByUser(
            $this->tokenStorage->getToken()->getUser()
        );
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

    public function searchQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->userInformationRepository->searchQueryBuilder($criteria);
    }
}