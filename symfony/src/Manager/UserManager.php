<?php

namespace App\Manager;

use App\Entity\Structure;
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

    public function findOneByExternalId(string $platform, string $externalId) : ?User
    {
        return $this->userRepository->findOneByExternalId($platform, $externalId);
    }

    public function changeLocale(User $user, string $locale)
    {
        $user->setLocale($locale);

        $this->userRepository->save($user);
    }

    public function changeVolunteer(string $platform, User $user, string $externalId)
    {
        $volunteer = $this->volunteerManager->findOneByNivol($user->getPlatform(), $externalId);

        if (!$volunteer) {
            $user->setVolunteer(null);
            $user->getStructures()->clear();

            $this->save($user);

            return;
        }

        if ($user->isLocked()) {
            return;
        }

        $user->setVolunteer($volunteer);

        $structures = $this->structureManager->findCallableStructuresForVolunteer($platform, $volunteer);
        $user->updateStructures($structures);

        $this->save($user);
    }

    public function getUserStructuresQueryBuilder(string $platform, User $user) : QueryBuilder
    {
        return $this->structureManager->getStructuresQueryBuilderForUser(
            $platform,
            $user
        );
    }

    public function searchQueryBuilder(?string $criteria, ?bool $onlyAdmins, ?bool $onlyDevelopers) : QueryBuilder
    {
        return $this->userRepository->searchQueryBuilder($criteria, $onlyAdmins, $onlyDevelopers);
    }

    public function getRedCallUsersInStructure(Structure $structure) : array
    {
        return $this->userRepository->getRedCallUsersInStructure($structure);
    }
}