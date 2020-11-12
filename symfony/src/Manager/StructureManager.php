<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Repository\StructureRepository;
use Doctrine\ORM\QueryBuilder;

class StructureManager
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var StructureRepository
     */
    private $structureRepository;

    public function __construct(StructureRepository $structureRepository)
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @required
     */
    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function find(int $id) : ?Structure
    {
        return $this->structureRepository->find($id);
    }

    public function findOneByName(string $name) : ?Structure
    {
        return $this->structureRepository->findOneByName($name);
    }

    public function findOneByIdentifier(string $identifier) : ?Structure
    {
        return $this->structureRepository->findOneByIdentifier($identifier);
    }

    public function save(Structure $structure)
    {
        $this->structureRepository->save($structure);
    }

    public function findCallableStructuresForVolunteer(Volunteer $volunteer) : array
    {
        return $this->structureRepository->findCallableStructuresForVolunteer($volunteer);
    }

    public function findCallableStructuresForStructure(Structure $structure) : array
    {
        return $this->structureRepository->findCallableStructuresForStructure($structure);
    }

    public function getTagCountByStructuresForCurrentUser() : array
    {
        $rows = $this->structureRepository->getTagCountByStructuresForUser(
            $this->userManager->findForCurrentUser()
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['structure_id']][$row['tag_id']] = $row['count'];
        }

        return $counts;
    }

    public function getVolunteerCountByStructuresForCurrentUser() : array
    {
        return $this->structureRepository->getVolunteerCountByStructuresForUser(
            $this->userManager->findForCurrentUser()
        );
    }

    public function searchAllQueryBuilder(?string $criteria) : QueryBuilder
    {
        return $this->structureRepository->searchAllQueryBuilder($criteria);
    }

    public function searchAll(?string $criteria, int $maxResults) : array
    {
        return $this->structureRepository->searchAll($criteria, $maxResults);
    }

    public function searchForCurrentUserQueryBuilder(?string $criteria) : QueryBuilder
    {
        return $this->structureRepository->searchForUserQueryBuilder(
            $this->userManager->findForCurrentUser(),
            $criteria,
            true
        );
    }

    public function synchronizeWithPegass()
    {
        $this->structureRepository->synchronizeWithPegass();
    }

    public function getStructuresQueryBuilderForUser(User $user) : QueryBuilder
    {
        return $this->structureRepository->searchForUserQueryBuilder($user, null, true);
    }

    public function getStructuresForUser(User $user) : array
    {
        return $this->structureRepository->searchForUser($user, null, 0xFFFFFFFF, true);
    }
}