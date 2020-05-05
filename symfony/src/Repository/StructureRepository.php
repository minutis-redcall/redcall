<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Entity\Volunteer;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
     * @param string $identifier
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function disableByIdentifier(string $identifier)
    {
        $this->createQueryBuilder('s')
             ->update()
             ->set('s.enabled', ':enabled')
             ->setParameter('enabled', false)
             ->where('s.identifier = :identifier')
             ->setParameter('identifier', $identifier)
             ->getQuery()
             ->execute();
    }

    /**
     * @param string $identifier
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function enableByIdentifier(string $identifier)
    {
        $this->createQueryBuilder('s')
            ->update()
            ->set('s.enabled', ':enabled')
            ->setParameter('enabled', true)
            ->where('s.identifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->execute();
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
    public function findCallableStructuresForVolunteer(Volunteer $volunteer): array
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
    public function findCallableStructuresForStructure(Structure $structure): array
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

    /**
     * @param array $structures
     *
     * @return array
     */
    public function countVolunteersInStructures(array $structures): array
    {
        $ids = array_map(function (Structure $structure) {
            return $structure->getId();
        }, $structures);

        $rows = $this->createQueryBuilder('s')
                     ->select('s.id as structure_id, COUNT(v.id) as count')
                     ->join('s.volunteers', 'v')
                     ->where('s.id IN (:ids)')
                     ->andWhere('s.enabled = true')
                     ->setParameter('ids', $ids)
                     ->andWhere('v.enabled = true')
                     ->groupBy('s.id')
                     ->getQuery()
                     ->getArrayResult();

        return array_combine(
            array_column($rows, 'structure_id'),
            array_column($rows, 'count')
        );
    }

    /**
     * @param UserInformation $user
     *
     * @return array
     */
    public function getTagCountByStructuresForUser(UserInformation $user): array
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

    public function getVolunteerCountByStructuresForUser(UserInformation $user): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.id as structure_id, COUNT(v.id) as count')
            ->join('s.users', 'u')
            ->join('s.volunteers', 'v')
            ->where('u.id = :id')
            ->andWhere('v.enabled = true')
            ->andWhere('s.enabled = true')
            ->setParameter('id', $user->getId())
            ->groupBy('s.id')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['structure_id']] = $row['count'];
        }

        return $counts;
    }

    /**
     * @param UserInformation $userInformation
     *
     * @return QueryBuilder
     */
    public function getStructuresForAdminQueryBuilder(UserInformation $userInformation): QueryBuilder
    {
        return $this->createQueryBuilder('s')
                    ->join('s.users', 'u')
                    ->where('u.id = :id')
                    ->setParameter('id', $userInformation->getId())
                    ->andWhere('s.enabled = true')
                    ->orderBy('s.identifier', 'asc');
    }

    /**
     * @param UserInformation $userInformation
     *
     * @return QueryBuilder
     */
    public function getStructuresForUserQueryBuilder(UserInformation $userInformation): QueryBuilder
    {
        return $this->getStructuresForAdminQueryBuilder($userInformation);
    }

    /**
     * @param string $criteria
     *
     * @return QueryBuilder
     */
    public function searchAllQueryBuilder(?string $criteria): QueryBuilder
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

    /**
     * @param string|null $criteria
     * @param int         $maxResults
     *
     * @return array
     */
    public function searchAll(?string $criteria, int $maxResults): array
    {
        return $this
            ->searchAllQueryBuilder($criteria)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param UserInformation $user
     * @param string|null     $criteria
     *
     * @return QueryBuilder
     */
    public function searchForUserQueryBuilder(UserInformation $user, ?string $criteria, bool $onlyEnabled = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.users', 'u')
            ->where('u.id = :user_id')
            ->setParameter('user_id', $user->getId())
        ;

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

    public function searchForUser(UserInformation $user, ?string $criteria, int $maxResults, bool $onlyEnabled = false): array
    {
        return $this
            ->searchForUserQueryBuilder($user, $criteria, $onlyEnabled)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function synchronizeWithPegass()
    {
        foreach ([false, true] as $enabled) {
            $qb = $this->createQueryBuilder('s');

            $sub = $this->_em->createQueryBuilder()
                ->select('p.identifier')
                ->from(Pegass::class, 'p')
                ->where('p.type = :type')
                ->andWhere('p.enabled = :enabled');

            $qb
                ->setParameter('type', Pegass::TYPE_STRUCTURE)
                ->setParameter('enabled', $enabled);

            $qb
                ->update()
                ->set('s.enabled', ':enabled')
                ->where($qb->expr()->in('s.identifier', $sub->getDQL()))
                ->getQuery()
                ->execute();
        }
    }
}
