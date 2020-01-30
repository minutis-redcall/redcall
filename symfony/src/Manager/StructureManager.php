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
     * @var StructureRepository
     */
    private $structureRepository;

    /**
     * @param UserInformationManager $userInformationManager
     * @param StructureRepository    $structureRepository
     */
    public function __construct(UserInformationManager $userInformationManager,
        StructureRepository $structureRepository)
    {
        $this->userInformationManager = $userInformationManager;
        $this->structureRepository    = $structureRepository;
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
     * @param UserInformation $user
     * @param string          $criteria
     *
     * @return QueryBuilder
     */
    public function searchForCurrentUser(?string $criteria): QueryBuilder
    {
        return $this->structureRepository->searchForUser(
            $this->userInformationManager->findForCurrentUser(),
            $criteria
        );
    }

    public function expireAll()
    {
        $this->structureRepository->expireAll();
    }
}