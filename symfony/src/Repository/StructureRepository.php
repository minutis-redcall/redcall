<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Campaign;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Platform;
use App\Security\Helper\Security;
use App\Entity\Pegass;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Hierarchy deepness is limited to 5.
 *
 * @method Structure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Structure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Structure[]    findAll()
 * @method Structure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StructureRepository extends BaseRepository
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security, ManagerRegistry $registry)
    {
        parent::__construct($registry, Structure::class);

        $this->security = $security;
    }

    public function findOneByExternalIdAndCurrentPlatform(string $externalId)
    {
        return $this->findOneBy([
            'platform'   => $this->security->getPlatform(),
            'externalId' => $externalId,
        ]);
    }

    public function findOneByExternalId(string $platform, string $externalId) : ?Structure
    {
        return $this->findOneBy([
            'platform'   => $platform,
            'externalId' => $externalId,
        ]);
    }

    public function findOneByName(string $platform, string $name)
    {
        return $this->findOneBy([
            'platform' => $platform,
            'name'     => $name,
        ]);
    }

    /**
     * This method perform nested search of all volunteer's structures
     *
     * Each structure can have children structures:
     * - an AS can call its volunteers
     * - an UL can call some AS + its volunteers
     * - a DT can call its ULs + all their ASs
     *
     * @param Volunteer $volunteer
     *
     * @return array
     */
    public function findCallableStructuresForVolunteer(string $platform, Volunteer $volunteer) : array
    {
        $structures = $this->createQueryBuilder('s')
                           ->select('
                                s.id as s1,
                                ss.id as s2,
                                sss.id as s3,
                                ssss.id as s4,
                                sssss.id as s5'
                           )
                           ->innerJoin('s.volunteers', 'v')
                           ->leftJoin('s.childrenStructures', 'ss')
                           ->leftJoin('ss.childrenStructures', 'sss')
                           ->leftJoin('sss.childrenStructures', 'ssss')
                           ->leftJoin('ssss.childrenStructures', 'sssss')
                           ->where('v.id = :id')
                           ->setParameter('id', $volunteer->getId())
                           ->getQuery()
                           ->getArrayResult();

        $ids = array_filter(array_unique(array_merge(
            array_column($structures, 's1'),
            array_column($structures, 's2'),
            array_column($structures, 's3'),
            array_column($structures, 's4'),
            array_column($structures, 's5')
        )));

        return $this->createQueryBuilder('s')
                    ->where('s.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $platform)
                    ->andWhere('s.enabled = true')
                    ->getQuery()
                    ->getResult();
    }

    /**
     * This method performs nested search of all children structures of a structure
     *
     * Each structure can have children structures:
     * - an AS can call its volunteers
     * - an UL can call some AS + its volunteers
     * - a DT can call its ULs + all their ASs
     *
     * @param Volunteer $volunteer
     *
     * @return array
     */
    public function findCallableStructuresForStructure(string $platform, Structure $structure) : array
    {
        $structures = $this->createQueryBuilder('s')
                           ->select('
                                s.id as s1,
                                ss.id as s2,
                                sss.id as s3,
                                ssss.id as s4,
                                sssss.id as s5'
                           )
                           ->leftJoin('s.childrenStructures', 'ss')
                           ->leftJoin('ss.childrenStructures', 'sss')
                           ->leftJoin('sss.childrenStructures', 'ssss')
                           ->leftJoin('ssss.childrenStructures', 'sssss')
                           ->where('s.id = :id')
                           ->setParameter('id', $structure->getId())
                           ->getQuery()
                           ->getArrayResult();

        $ids = array_filter(array_unique(array_merge(
            array_column($structures, 's1'),
            array_column($structures, 's2'),
            array_column($structures, 's3'),
            array_column($structures, 's4'),
            array_column($structures, 's5')
        )));

        return $this->createQueryBuilder('s')
                    ->where('s.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $platform)
                    ->andWhere('s.enabled = true')
                    ->getQuery()
                    ->getResult();
    }

    public function getStructuresForUserQueryBuilder(string $platform, User $user) : QueryBuilder
    {
        return $this->createQueryBuilder('s')
                    ->join('s.users', 'u')
                    ->where('u.id = :id')
                    ->setParameter('id', $user->getId())
                    ->andWhere('s.enabled = true')
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $platform)
                    ->orderBy('s.externalId', 'asc');
    }

    public function searchAllQueryBuilder(string $platform, ?string $criteria, bool $onlyEnabled = true) : QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('s')
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', $platform);

        if ($onlyEnabled) {
            $qb->andWhere('s.enabled = :enabled')
               ->setParameter('enabled', true);
        }

        if ($criteria) {
            $qb->andWhere('s.externalId LIKE :criteria OR s.name LIKE :criteria')
               ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
        }

        $qb->orderBy('s.name');

        return $qb;
    }

    public function searchAll(string $platform, ?string $criteria, int $maxResults) : array
    {
        return $this
            ->searchAllQueryBuilder($platform, $criteria)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function searchForUserQueryBuilder(string $platform,
        User $user,
        ?string $criteria,
        bool $onlyEnabled = true) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
                   ->join('s.users', 'u')
                   ->where('u.id = :user_id')
                   ->setParameter('user_id', $user->getId())
                   ->andWhere('s.platform = :platform')
                   ->setParameter('platform', $platform);

        if ($onlyEnabled) {
            $qb->andWhere('s.enabled = :enabled')
               ->setParameter('enabled', true);
        }

        if ($criteria) {
            $qb->andWhere('s.externalId LIKE :criteria OR s.name LIKE :criteria')
               ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
        }

        $qb->orderBy('s.enabled DESC, s.name');

        return $qb;
    }

    public function synchronizeWithPegass()
    {
        $qb = $this->createQueryBuilder('s');

        $sub = $this->_em->createQueryBuilder()
                         ->select('p.identifier')
                         ->from(Pegass::class, 'p')
                         ->where('p.type = :type')
                         ->andWhere('p.enabled = :enabled');

        $qb
            ->setParameter('type', Pegass::TYPE_STRUCTURE)
            ->setParameter('enabled', false);

        $qb
            ->update()
            ->set('s.enabled', ':enabled')
            ->where($qb->expr()->in('s.externalId', $sub->getDQL()))
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', Platform::FR)
            ->getQuery()
            ->execute();
    }

    public function getCampaignStructures(string $platform, Campaign $campaign) : array
    {
        return $this
            ->createQueryBuilder('s')
            ->distinct()
            ->join('s.volunteers', 'v')
            ->join('v.messages', 'm')
            ->join('m.communication', 'co')
            ->join('co.campaign', 'c')
            ->where('s.enabled = true')
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', $platform)
            ->andWhere('c.id = :campaign_id')
            ->setParameter('campaign_id', $campaign->getId())
            ->getQuery()
            ->getResult();
    }

    public function countRedCallUsersQueryBuilder(string $platform, QueryBuilder $qb) : QueryBuilder
    {
        return (clone $qb)
            ->select('s.id as structure_id, COUNT(su) AS count')
            ->join('s.users', 'su')
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', $platform)
            ->andWhere('su.isTrusted = true')
            ->groupBy('s.id');
    }

    public function getStructureHierarchyForCurrentUser(string $platform, User $user) : array
    {
        $rows = $this
            ->getStructuresForUserQueryBuilder($platform, $user)
            ->select('s.id, c.id as child_id, c.enabled as child_enabled')
            ->leftJoin('s.childrenStructures', 'c')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $index => $row) {
            if (!$row['child_enabled']) {
                $rows[$index]['child_id'] = null;
            }
        }

        return $rows;
    }

    public function getVolunteerLocalCounts(string $platform, array $structureIds) : array
    {
        return $this
            ->createQueryBuilder('s')
            ->select('s.id, s.name, COUNT(DISTINCT v) AS count')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $structureIds, Connection::PARAM_INT_ARRAY)
            ->andWhere('s.enabled = true')
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', $platform)
            ->leftJoin('s.volunteers', 'v')
            ->andWhere('v.enabled = true OR v.enabled IS NULL')
            ->groupBy('s.id')
            ->getQuery()
            ->getArrayResult();
    }

    public function getDescendantStructures(string $platform, array $structureIds) : array
    {
        for ($i = 0; $i < 5; $i++) {
            $structureIds = array_merge(
                $structureIds,
                array_column($this
                    ->createQueryBuilder('s')
                    ->select('s.id')
                    ->where('s.enabled = true')
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $platform)
                    ->join('s.parentStructure', 'p')
                    ->andWhere('p.id IN (:ids)')
                    ->setParameter('ids', $structureIds, Connection::PARAM_INT_ARRAY)
                    ->getQuery()
                    ->getArrayResult(), 'id')
            );
        }

        return $structureIds;
    }

    public function searchAllForVolunteerQueryBuilder(string $platform,
        Volunteer $volunteer,
        ?string $criteria,
        bool $enabled) : QueryBuilder
    {
        $qb = $this->searchAllQueryBuilder($platform, $criteria, $enabled);

        return $this->forVolunteer($qb, $volunteer);
    }

    public function searchForVolunteerAndCurrentUserQueryBuilder(string $platform,
        User $user,
        Volunteer $volunteer,
        ?string $criteria,
        bool $enabled) : QueryBuilder
    {
        $qb = $this->searchForUserQueryBuilder($platform, $user, $criteria, $enabled);

        return $this->forVolunteer($qb, $volunteer);
    }

    private function forVolunteer(QueryBuilder $qb, Volunteer $volunteer) : QueryBuilder
    {
        return $qb
            ->join('s.volunteers', 'v')
            ->andWhere('v.id = :volunteer')
            ->setParameter('volunteer', $volunteer);
    }
}
