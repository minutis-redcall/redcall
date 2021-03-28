<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Campaign;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Enum\Platform;
use App\Security\Helper\Security;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
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

    public function findOneByIdentifier(string $identifier) : ?Structure
    {
        return $this->findOneBy([
            'platform'   => $this->security->getPlatform(),
            'identifier' => $identifier,
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
    public function findCallableStructuresForVolunteer(Volunteer $volunteer) : array
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
                           ->andWhere('s.enabled IS NULL OR s.enabled = true')
                           ->andWhere('ss.enabled IS NULL OR ss.enabled = true')
                           ->andWhere('sss.enabled IS NULL OR sss.enabled = true')
                           ->andWhere('ssss.enabled IS NULL OR ssss.enabled = true')
                           ->andWhere('sssss.enabled IS NULL OR sssss.enabled = true')
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
                    ->setParameter('platform', $this->security->getPlatform())
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
    public function findCallableStructuresForStructure(Structure $structure) : array
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
                           ->andWhere('s.enabled IS NULL OR s.enabled = true')
                           ->andWhere('ss.enabled IS NULL OR ss.enabled = true')
                           ->andWhere('sss.enabled IS NULL OR sss.enabled = true')
                           ->andWhere('ssss.enabled IS NULL OR ssss.enabled = true')
                           ->andWhere('sssss.enabled IS NULL OR sssss.enabled = true')
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
                    ->setParameter('platform', $this->security->getPlatform())
                    ->getQuery()
                    ->getResult();
    }

    public function getStructuresForUserQueryBuilder(User $user) : QueryBuilder
    {
        return $this->createQueryBuilder('s')
                    ->join('s.users', 'u')
                    ->where('u.id = :id')
                    ->setParameter('id', $user->getId())
                    ->andWhere('s.enabled = true')
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $this->security->getPlatform())
                    ->orderBy('s.identifier', 'asc');
    }

    public function searchAllQueryBuilder(?string $criteria, bool $onlyEnabled = true) : QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('s')
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', $this->security->getPlatform());

        if ($onlyEnabled) {
            $qb->andWhere('s.enabled = :enabled')
               ->setParameter('enabled', true);
        }

        if ($criteria) {
            $qb->andWhere('s.identifier LIKE :criteria OR s.name LIKE :criteria')
               ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
        }

        $qb->orderBy('s.name');

        return $qb;
    }

    public function searchAll(?string $criteria, int $maxResults) : array
    {
        return $this
            ->searchAllQueryBuilder($criteria)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function searchForUserQueryBuilder(User $user, ?string $criteria, bool $onlyEnabled = true) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
                   ->join('s.users', 'u')
                   ->where('u.id = :user_id')
                   ->setParameter('user_id', $user->getId())
                   ->andWhere('s.platform = :platform')
                   ->setParameter('platform', $this->security->getPlatform());

        if ($onlyEnabled) {
            $qb->andWhere('s.enabled = :enabled')
               ->setParameter('enabled', true);
        }

        if ($criteria) {
            $qb->andWhere('s.identifier LIKE :criteria OR s.name LIKE :criteria')
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
            ->where($qb->expr()->in('s.identifier', $sub->getDQL()))
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', Platform::FR)
            ->getQuery()
            ->execute();
    }

    public function getCampaignStructures(Campaign $campaign) : array
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
            ->setParameter('platform', $this->security->getPlatform())
            ->andWhere('c.id = :campaign_id')
            ->setParameter('campaign_id', $campaign->getId())
            ->getQuery()
            ->getResult();
    }

    public function countRedCallUsersQueryBuilder(QueryBuilder $qb) : QueryBuilder
    {
        return (clone $qb)
            ->select('s.id as structure_id, COUNT(su) AS count')
            ->join('s.users', 'su')
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', $this->security->getPlatform())
            ->andWhere('su.isTrusted = true')
            ->groupBy('s.id');
    }

    public function getStructureHierarchyForCurrentUser(User $user) : array
    {
        return $this
            ->getStructuresForUserQueryBuilder($user)
            ->select('s.id, c.id as child_id')
            ->leftJoin('s.childrenStructures', 'c')
            ->andWhere('c.enabled IS NULL OR c.enabled = true')
            ->getQuery()
            ->getArrayResult();
    }

    public function getVolunteerLocalCounts(array $structureIds) : array
    {
        return $this
            ->createQueryBuilder('s')
            ->select('s.id, s.name, COUNT(DISTINCT v) AS count')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $structureIds, Connection::PARAM_INT_ARRAY)
            ->andWhere('s.enabled = true')
            ->andWhere('s.platform = :platform')
            ->setParameter('platform', $this->security->getPlatform())
            ->leftJoin('s.volunteers', 'v')
            ->andWhere('v.enabled = true OR v.enabled IS NULL')
            ->groupBy('s.id')
            ->getQuery()
            ->getArrayResult();
    }

    public function getDescendantStructures(array $structureIds) : array
    {
        for ($i = 0; $i < 5; $i++) {
            $structureIds = array_merge(
                $structureIds,
                array_column($this
                    ->createQueryBuilder('s')
                    ->select('s.id')
                    ->where('s.enabled = true')
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $this->security->getPlatform())
                    ->join('s.parentStructure', 'p')
                    ->andWhere('p.id IN (:ids)')
                    ->setParameter('ids', $structureIds, Connection::PARAM_INT_ARRAY)
                    ->getQuery()
                    ->getArrayResult(), 'id')
            );
        }

        return $structureIds;
    }
}
