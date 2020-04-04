<?php

namespace App\Manager;

use App\Entity\Structure;
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

    /**
     * @param string $nivol
     *
     * @return UserInformation|null
     */
    public function findOneByNivol(string $nivol): ?UserInformation
    {
        return $this->userInformationRepository->findOneByNivol($nivol);
    }

    /**
     * @param UserInformation $userInformation
     * @param string          $nivol
     */
    public function updateNivol(UserInformation $userInformation, string $nivol)
    {
        $this->removeRedCallStructure($userInformation);

        $volunteer = $this->volunteerManager->findOneByNivol($nivol);

        if (!$volunteer) {
            $userInformation->setNivol(null);
            $userInformation->setVolunteer(null);
            $userInformation->getStructures()->clear();

            $this->save($userInformation);

            return;
        }

        $userInformation->setNivol($nivol);
        $userInformation->setVolunteer($volunteer);

        $this->addRedCallStructure($userInformation);

        $structures = $this->structureManager->findCallableStructuresForVolunteer($volunteer);
        $userInformation->updateStructures($structures);

        $this->save($userInformation);
    }

    /**
     * @param UserInformation $userInformation
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function removeRedCallStructure(UserInformation $userInformation)
    {
        $volunteer = $userInformation->getVolunteer();

        $structure = $this->structureManager->findOneByIdentifier(Structure::REDCALL_STRUCTURE);
        if (!$structure) {
            return;
        }

        if ($volunteer) {
            $volunteer->removeStructure($structure);
            $this->volunteerManager->save($volunteer);
        }

        $userInformation->removeStructure($structure);
        $this->save($userInformation);
    }

    /**
     * @param UserInformation $userInformation
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function addRedCallStructure(UserInformation $userInformation)
    {
        $volunteer = $userInformation->getVolunteer();

        $structure = $this->structureManager->findOneByIdentifier(Structure::REDCALL_STRUCTURE);
        if (!$structure) {
            return;
        }

        if ($volunteer) {
            $volunteer->addStructure($structure);
            $this->volunteerManager->save($volunteer);
        }

        $userInformation->addStructure($structure);
        $this->save($userInformation);
    }

    /**
     * @param string|null $criteria
     *
     * @return QueryBuilder
     */
    public function searchQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->userInformationRepository->searchQueryBuilder($criteria);
    }
}