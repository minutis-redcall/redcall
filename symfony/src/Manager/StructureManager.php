<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Entity\Volunteer;
use App\Repository\StructureRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class StructureManager
{
    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureRepository
     */
    private $structureRepository;

    /**
     * @param UserInformationManager $userInformationManager
     * @param VolunteerManager       $volunteerManager
     * @param StructureRepository    $structureRepository
     */
    public function __construct(UserInformationManager $userInformationManager, VolunteerManager $volunteerManager, StructureRepository $structureRepository)
    {
        $this->userInformationManager = $userInformationManager;
        $this->volunteerManager = $volunteerManager;
        $this->structureRepository = $structureRepository;
    }

    /**
     * @param int $id
     *
     * @return Structure|null
     */
    public function find(int $id): ?Structure
    {
        return $this->structureRepository->find($id);
    }

    /**
     * @param string $name
     *
     * @return Structure|null
     */
    public function findOneByName(string $name): ?Structure
    {
        return $this->structureRepository->findOneByName($name);
    }

    /**
     * @return array
     */
    public function listStructureIdentifiers(): array
    {
        return $this->structureRepository->listStructureIdentifiers();
    }

    /**
     * @param string $identifier
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function disableByIdentifier(string $identifier)
    {
        $this->structureRepository->disableByIdentifier($identifier);
    }

    /**
     * @param string $identifier
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function enableByIdentifier(string $identifier)
    {
        $this->structureRepository->enableByIdentifier($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return Structure|null
     *
     * @throws NonUniqueResultException
     */
    public function findOneByIdentifier(string $identifier): ?Structure
    {
        return $this->structureRepository->findOneByIdentifier($identifier);
    }

    /**
     * @param Structure $structure
     */
    public function save(Structure $structure)
    {
        $this->structureRepository->save($structure);
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return array
     */
    public function findCallableStructuresForVolunteer(Volunteer $volunteer): array
    {
        return $this->structureRepository->findCallableStructuresForVolunteer($volunteer);
    }

    /**
     * @param Structure $volunteer
     *
     * @return array
     */
    public function findCallableStructuresForStructure(Structure $structure): array
    {
        return $this->structureRepository->findCallableStructuresForStructure($structure);
    }

    /**
     * @param array $structures
     *
     * @return array
     */
    public function countVolunteersInStructures(array $structures): array
    {
        return $this->structureRepository->countVolunteersInStructures($structures);
    }

    /**
     * @return array
     */
    public function getTagCountByStructuresForCurrentUser(): array
    {
        $rows = $this->structureRepository->getTagCountByStructuresForUser(
            $this->userInformationManager->findForCurrentUser()
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['structure_id']][$row['tag_id']] = $row['count'];
        }

        return $counts;
    }

    /**
     * @param string $criteria
     *
     * @return QueryBuilder
     */
    public function searchAllQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->structureRepository->searchAllQueryBuilder($criteria);
    }

    /**
     * @param string|null $criteria
     * @param int         $maxResults
     *
     * @return array
     */
    public function searchAll(?string $criteria, int $maxResults): array
    {
        return $this->structureRepository->searchAll($criteria, $maxResults);
    }

    /**
     * @param UserInformation $user
     * @param string          $criteria
     *
     * @return QueryBuilder
     */
    public function searchForCurrentUserQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->structureRepository->searchForUserQueryBuilder(
            $this->userInformationManager->findForCurrentUser(),
            $criteria
        );
    }

    public function createRedCallStructure()
    {
        if (!$this->structureRepository->findOneByName('RedCall')) {
            $structure = new Structure();
            $structure->setIdentifier(Structure::REDCALL_STRUCTURE);
            $structure->setName('RedCall');
            $structure->setType('UL');
            $structure->setEnabled(true);
            $this->structureRepository->save($structure);

            $users = $this->userInformationManager->findAll();
            foreach ($users as $user) {
                $volunteer = $user->getVolunteer();
                if (!$volunteer) {
                    continue;
                }
                $structure->addVolunteer($volunteer);
                $user->addStructure($structure);
                $this->userInformationManager->save($user);
                $volunteer->addStructure($structure);
                $this->volunteerManager->save($volunteer);
            }
            $this->structureRepository->save($structure);
        }
    }
}