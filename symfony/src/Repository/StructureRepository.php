<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Structure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Structure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Structure[]    findAll()
 * @method Structure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StructureRepository extends BaseRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Structure::class);
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
                    ->getQuery()
                    ->getResult();
    }

    public function getTagCountByStructuresForUser(User $user) : array
    {
        return $this->createQueryBuilder('s')
                    ->select('s.id as structure_id, t.id as tag_id, COUNT(v.id) as count')
                    ->join('s.users', 'u')
                    ->join('s.volunteers', 'v')
                    ->join('v.tags', 't')
                    ->where('u.id = :id')
                    ->andWhere('v.enabled = true')
                    ->andWhere('s.enabled = true')
                    ->setParameter('id', $user->getId())
                    ->orderBy('t.id', 'ASC')
                    ->groupBy('s.id', 't.id')
                    ->getQuery()
                    ->getArrayResult();
    }

    public function getVolunteerCountByStructuresForUser(User $user) : array
    {
        $rows = $this->createQueryBuilder('s')
                     ->select('s.id as structure_id, p.id as parent_id, COUNT(DISTINCT v.id) as count')
                     ->join('s.users', 'u')
                     ->join('s.volunteers', 'v')
                     ->leftJoin('s.parentStructure', 'p')
                     ->where('u.id = :id')
                     ->andWhere('v.enabled = true')
                     ->andWhere('s.enabled = true')
                     ->setParameter('id', $user->getId())
                     ->groupBy('s.id')
                     ->getQuery()
                     ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['structure_id']]['local'] = $row['count'];
            if (!isset($counts[$row['structure_id']]['global'])) {
                $counts[$row['structure_id']]['global'] = 0;
            }
            $counts[$row['structure_id']]['global'] += $row['count'];

            if ($row['parent_id']) {
                if (!isset($counts[$row['parent_id']]['global'])) {
                    $counts[$row['parent_id']]['global'] = 0;
                }
                $counts[$row['parent_id']]['global'] += $row['count'];
            }
        }

        return $counts;
    }

    public function getStructuresForAdminQueryBuilder(User $user) : QueryBuilder
    {
        return $this->createQueryBuilder('s')
                    ->join('s.users', 'u')
                    ->where('u.id = :id')
                    ->setParameter('id', $user->getId())
                    ->andWhere('s.enabled = true')
                    ->orderBy('s.identifier', 'asc');
    }

    public function getStructuresForUserQueryBuilder(User $user) : QueryBuilder
    {
        return $this->getStructuresForAdminQueryBuilder($user);
    }

    public function searchAllQueryBuilder(?string $criteria) : QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('s');

        if ($criteria) {
            $qb->andWhere('s.identifier LIKE :criteria OR s.name LIKE :criteria')
               ->setParameter('criteria', sprintf('%%%s%%', $criteria));
        }

        $qb->orderBy('s.name');

        return $qb;
    }

    public function searchAll(?string $criteria, int $maxResults) : array
    {
        return $this
            ->searchAllQueryBuilder($criteria)
            ->andWhere('s.enabled = true')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function searchForUserQueryBuilder(User $user, ?string $criteria, bool $onlyEnabled = false) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
                   ->join('s.users', 'u')
                   ->where('u.id = :user_id')
                   ->setParameter('user_id', $user->getId());

        if ($onlyEnabled) {
            $qb->andWhere('s.enabled = true');
        }

        if ($criteria) {
            $qb->andWhere('s.identifier LIKE :criteria OR s.name LIKE :criteria')
               ->setParameter('criteria', sprintf('%%%s%%', $criteria));
        }

        $qb->orderBy('s.name');

        return $qb;
    }

    public function searchForUser(User $user, ?string $criteria, int $maxResults, bool $onlyEnabled = false) : array
    {
        return $this
            ->searchForUserQueryBuilder($user, $criteria, $onlyEnabled)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
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
            ->getQuery()
            ->execute();
    }
}
