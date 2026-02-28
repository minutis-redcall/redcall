<?php

namespace App\Manager;

use App\Entity\Campaign;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Repository\StructureRepository;
use App\Security\Helper\Security;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class StructureManager
{
    /**
     * @var StructureRepository
     */
    private $structureRepository;

    /**
     * @var Security
     */
    private $security;

    public function __construct(StructureRepository $structureRepository, Security $security)
    {
        $this->structureRepository = $structureRepository;
        $this->security            = $security;
    }

    public function find(int $id) : ?Structure
    {
        return $this->structureRepository->find($id);
    }

    public function findOneByName(string $name) : ?Structure
    {
        return $this->structureRepository->findOneByName($name);
    }

    public function findOneByExternalId(string $externalId) : ?Structure
    {
        return $this->structureRepository->findOneByExternalId($externalId);
    }

    public function save(Structure $structure)
    {
        $this->structureRepository->save($structure);
    }

    public function remove(Structure $structure)
    {
        $this->structureRepository->remove($structure);
    }

    public function findCallableStructuresForVolunteer(Volunteer $volunteer) : array
    {
        return $this->structureRepository->findCallableStructuresForVolunteer($volunteer);
    }

    public function findCallableStructuresForStructure(Structure $structure) : array
    {
        return $this->structureRepository->findCallableStructuresForStructure($structure);
    }

    public function searchAllQueryBuilder(?string $criteria, bool $enabled) : QueryBuilder
    {
        return $this->structureRepository->searchAllQueryBuilder($criteria, $enabled);
    }

    public function searchAll(?string $criteria, int $maxResults) : array
    {
        return $this->structureRepository->searchAll($criteria, $maxResults);
    }

    public function searchForCurrentUser(?string $criteria, int $maxResults)
    {
        return $this
            ->searchForCurrentUserQueryBuilder($criteria, $maxResults)
            ->getQuery()
            ->getResult();
    }

    public function searchForCurrentUserQueryBuilder(?string $criteria, bool $enabled) : QueryBuilder
    {
        return $this->structureRepository->searchForUserQueryBuilder(
            $this->security->getUser(),
            $criteria,
            $enabled
        );
    }

    public function searchQueryBuilder(?string $criteria, bool $enabled) : QueryBuilder
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->searchAllQueryBuilder($criteria, $enabled);
        } else {
            return $this->searchForCurrentUserQueryBuilder($criteria, $enabled);
        }
    }

    public function searchAllForVolunteerQueryBuilder(Volunteer $volunteer,
        ?string $criteria = null,
        bool $enabled = true) : QueryBuilder
    {
        return $this->structureRepository->searchAllForVolunteerQueryBuilder(
            $volunteer,
            $criteria,
            $enabled
        );
    }

    public function searchForVolunteerAndCurrentUserQueryBuilder(Volunteer $volunteer,
        ?string $criteria = null,
        bool $enabled = true) : QueryBuilder
    {
        return $this->structureRepository->searchForVolunteerAndCurrentUserQueryBuilder(
            $this->security->getUser(),
            $volunteer,
            $criteria,
            $enabled
        );
    }

    public function searchForVolunteerQueryBuilder(Volunteer $volunteer,
        ?string $criteria,
        bool $enabled) : QueryBuilder
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->searchAllForVolunteerQueryBuilder($volunteer, $criteria, $enabled);
        } else {
            return $this->searchForVolunteerAndCurrentUserQueryBuilder($volunteer, $criteria, $enabled);
        }
    }

    public function synchronizeWithPegass()
    {
        $this->structureRepository->synchronizeWithPegass();
    }

    public function getStructuresQueryBuilderForUser(User $user) : QueryBuilder
    {
        return $this->structureRepository->searchForUserQueryBuilder($user, null);
    }

    public function getStructuresForUser(User $user) : array
    {
        $entities = $this
            ->structureRepository
            ->searchForUserQueryBuilder($user, null)
            ->getQuery()
            ->getResult();

        $structures = [];
        foreach ($entities as $entity) {
            /** @var Structure $entity */
            $structures[$entity->getId()] = $entity;
        }

        return $structures;
    }

    public function getStructureHierarchyForCurrentUser()
    {
        return $this->structureRepository->getStructureHierarchyForCurrentUser(
            $this->security->getUser()
        );
    }

    public function getCampaignStructures(Campaign $campaign) : array
    {
        return $this->structureRepository->getCampaignStructures($campaign);
    }

    public function countRedCallUsersQueryBuilder(QueryBuilder $queryBuilder) : QueryBuilder
    {
        return $this->structureRepository->countRedCallUsersQueryBuilder($queryBuilder);
    }

    public function countRedCallUsersInPager(Pagerfanta $pagerfanta) : array
    {
        $counts = [];
        foreach ($pagerfanta->getIterator() as $row) {
            $counts[$row['structure_id']] = $row['count'];
        }

        return $counts;
    }

    public function addStructureAndItsChildrenToVolunteer(Volunteer $volunteer, Structure $structure)
    {
        $structures = $this->findCallableStructuresForStructure($structure);

        foreach ($structures as $structure) {
            $volunteer->addStructure($structure);
        }
    }

    public function getVolunteerLocalCounts(array $structureIds) : array
    {
        return $this->structureRepository->getVolunteerLocalCounts($structureIds);
    }

    public function getDescendantStructures(array $structureIds) : array
    {
        return $this->structureRepository->getDescendantStructures($structureIds);
    }
}

