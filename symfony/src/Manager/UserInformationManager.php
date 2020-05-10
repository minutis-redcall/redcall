<?php

namespace App\Manager;

use App\Entity\UserInformation;
use App\Repository\UserInformationRepository;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserInformationManager
{
    /**
     * @var UserInformationRepository
     */
    private $userInformationRepository;

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
     * @param UserInformationRepository $userInformationRepository
     * @param VolunteerManager          $volunteerManager
     * @param TokenStorageInterface     $tokenStorage
     */
    public function __construct(UserInformationRepository $userInformationRepository,
        TokenStorageInterface $tokenStorage)
    {
        $this->userInformationRepository = $userInformationRepository;
        $this->tokenStorage              = $tokenStorage;
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
     * @return UserInformation[]
     */
    public function findAll(): array
    {
        return $this->userInformationRepository->findAll();
    }

    public function findForCurrentUser(): ?UserInformation
    {
        return $this->findOneByUser(
            $this->tokenStorage->getToken()->getUser()
        );
    }

    public function findOneByUser(AbstractUser $user): ?UserInformation
    {
        return $this->userInformationRepository->findOneByUser($user);
    }

    public function removeForUser(AbstractUser $user)
    {
        $this->userInformationRepository->removeForUser($user);
    }

    public function save(UserInformation $userInformation)
    {
        $this->userInformationRepository->save($userInformation);
    }

    public function findOneByNivol(string $nivol): ?UserInformation
    {
        return $this->userInformationRepository->findOneByNivol($nivol);
    }

    public function changeLocale(AbstractUser $user, string $locale)
    {
        $this->userInformationRepository->changeLocale($user, $locale);
    }

    public function updateNivol(UserInformation $userInformation, string $nivol)
    {
        $volunteer = $this->volunteerManager->findOneByNivol($nivol);

        if (!$volunteer) {
            $userInformation->setNivol(null);
            $userInformation->setVolunteer(null);
            $userInformation->getStructures()->clear();

            $this->save($userInformation);

            return;
        }

        if ($userInformation->isLocked()) {
            return;
        }

        $userInformation->setNivol($nivol);
        $userInformation->setVolunteer($volunteer);

        $structures = $this->structureManager->findCallableStructuresForVolunteer($volunteer);
        $userInformation->updateStructures($structures);

        $this->save($userInformation);
    }

    public function getCurrentUserStructuresQueryBuilder(): QueryBuilder
    {
        return $this->structureManager->getStructuresQueryBuilderForUser(
            $this->findForCurrentUser()
        );
    }

    public function getCurrentUserStructures(): array
    {
        return $this->structureManager->getStructuresForUser(
            $this->findForCurrentUser()
        );
    }

    public function searchQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->userInformationRepository->searchQueryBuilder($criteria);
    }
}