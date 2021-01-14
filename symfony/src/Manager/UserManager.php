<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Bundles\PasswordLoginBundle\Manager\UserManager as BaseUserManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserManager extends BaseUserManager
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param VolunteerManager      $volunteerManager
     * @param StructureManager      $structureManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(UserRepository $userRepository,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        TokenStorageInterface $tokenStorage)
    {
        parent::__construct($userRepository);

        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->tokenStorage     = $tokenStorage;
    }

    /**
     * @required
     *
     * @param VolunteerManager $campaignManager
     */
    public function setVolunteerManager(VolunteerManager $volunteerManager)
    {
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * @required
     *
     * @param StructureManager $campaignManager
     */
    public function setStructureManager(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * @return User[]
     */
    public function findAll() : array
    {
        return $this->userRepository->findAll();
    }

    public function findForCurrentUser() : ?User
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user || is_scalar($user)) {
            return null;
        }

        return $user;
    }

    public function findOneByNivol(string $nivol) : ?User
    {
        return $this->userRepository->findOneByNivol($nivol);
    }

    public function changeLocale(User $user, string $locale)
    {
        $user->setLocale($locale);

        $this->userRepository->save($user);
    }

    public function updateNivol(User $user, string $nivol)
    {
        $volunteer = $this->volunteerManager->findOneByNivol($nivol);

        if (!$volunteer) {
            $user->setNivol(null);
            $user->setVolunteer(null);
            $user->getStructures()->clear();

            $this->save($user);

            return;
        }

        if ($user->isLocked()) {
            return;
        }

        $user->setNivol($nivol);
        $user->setVolunteer($volunteer);

        $structures = $this->structureManager->findCallableStructuresForVolunteer($volunteer);
        $user->updateStructures($structures);

        $this->save($user);
    }

    public function getUserStructuresQueryBuilder(User $user) : QueryBuilder
    {
        return $this->structureManager->getStructuresQueryBuilderForUser(
            $user
        );
    }

    public function searchQueryBuilder(?string $criteria) : QueryBuilder
    {
        return $this->userRepository->searchQueryBuilder($criteria);
    }
}