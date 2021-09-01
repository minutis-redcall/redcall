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

    public function findOneByName(string $platform, string $name) : ?Structure
    {
        return $this->structureRepository->findOneByName($platform, $name);
    }

    public function findOneByExternalId(string $platform, string $externalId) : ?Structure
    {
        return $this->structureRepository->findOneByExternalId($platform, $externalId);
    }

    public function save(Structure $structure)
    {
        $this->structureRepository->save($structure);
    }

    public function remove(Structure $structure)
    {
        $this->structureRepository->remove($structure);
    }

    public function findCallableStructuresForVolunteer(string $platform, Volunteer $volunteer) : array
    {
        return $this->structureRepository->findCallableStructuresForVolunteer($platform, $volunteer);
    }

    public function findCallableStructuresForStructure(string $platform, Structure $structure) : array
    {
        return $this->structureRepository->findCallableStructuresForStructure($platform, $structure);
    }

    public function searchAllQueryBuilder(string $platform, ?string $criteria, bool $enabled) : QueryBuilder
    {
        return $this->structureRepository->searchAllQueryBuilder($platform, $criteria, $enabled);
    }

    public function searchAll(string $platform, ?string $criteria, int $maxResults) : array
    {
        return $this->structureRepository->searchAll($platform, $criteria, $maxResults);
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
            $this->security->getPlatform(),
            $this->security->getUser(),
            $criteria,
            $enabled
        );
    }

    public function searchQueryBuilder(?string $criteria, bool $enabled) : QueryBuilder
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->searchAllQueryBuilder($this->security->getPlatform(), $criteria, $enabled);
        } else {
            return $this->searchForCurrentUserQueryBuilder($criteria, $enabled);
        }
    }

    public function synchronizeWithPegass()
    {
        $this->structureRepository->synchronizeWithPegass();
    }

    public function getStructuresQueryBuilderForUser(string $platform, User $user) : QueryBuilder
    {
        return $this->structureRepository->searchForUserQueryBuilder($platform, $user, null);
    }

    public function getStructuresForUser(string $platform, User $user) : array
    {
        $entities = $this
            ->structureRepository
            ->searchForUserQueryBuilder($platform, $user, null)
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
            $this->security->getPlatform(),
            $this->security->getUser()
        );
    }

    public function getCampaignStructures(string $platform, Campaign $campaign) : array
    {
        return $this->structureRepository->getCampaignStructures($platform, $campaign);
    }

    public function countRedCallUsersQueryBuilder(string $platform, QueryBuilder $queryBuilder) : QueryBuilder
    {
        return $this->structureRepository->countRedCallUsersQueryBuilder($platform, $queryBuilder);
    }

    public function countRedCallUsersInPager(Pagerfanta $pagerfanta) : array
    {
        $counts = [];
        foreach ($pagerfanta->getIterator() as $row) {
            $counts[$row['structure_id']] = $row['count'];
        }

        return $counts;
    }

    public function getVolunteerLocalCounts(string $platform, array $structureIds) : array
    {
        return $this->structureRepository->getVolunteerLocalCounts($platform, $structureIds);
    }

    public function getDescendantStructures(string $platform, array $structureIds) : array
    {
        return $this->structureRepository->getDescendantStructures($platform, $structureIds);
    }
}

