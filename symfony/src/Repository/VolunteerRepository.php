<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Volunteer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Volunteer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Volunteer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerRepository extends BaseRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Volunteer::class);
    }

    public function disable(Volunteer $volunteer)
    {
        if ($volunteer->isEnabled()) {
            $volunteer->setEnabled(false);
            $this->save($volunteer);
        }
    }

    public function enable(Volunteer $volunteer)
    {
        if (!$volunteer->isEnabled()) {
            $volunteer->setEnabled(true);
            $this->save($volunteer);
        }
    }

    public function lock(Volunteer $volunteer)
    {
        if (!$volunteer->isLocked()) {
            $volunteer->setLocked(true);
            $this->save($volunteer);
        }
    }

    public function unlock(Volunteer $volunteer)
    {
        if ($volunteer->isLocked()) {
            $volunteer->setLocked(false);
            $this->save($volunteer);
        }
    }

    public function findOneByNivol(string $nivol) : ?Volunteer
    {
        return $this->findOneBy([
            'nivol' => ltrim($nivol, '0'),
        ]);
    }

    /**
     * @param string $keyword
     * @param int    $maxResults
     * @param User   $user
     *
     * @return Volunteer[]
     */
    public function searchForUser(User $user, ?string $keyword, int $maxResults, bool $onlyEnabled = false) : array
    {
        return $this->searchForUserQueryBuilder($user, $keyword, $onlyEnabled)
                    ->getQuery()
                    ->setMaxResults($maxResults)
                    ->getResult();
    }

    public function searchForUserQueryBuilder(User $user,
        ?string $keyword,
        bool $onlyEnabled = false,
        bool $onlyUsers = true) : QueryBuilder
    {
        $qb = $this->createAccessibleVolunteersQueryBuilder($user, $onlyEnabled);

        if ($keyword) {
            $this->addSearchCriteria($qb, $keyword);
        }

        if ($onlyUsers) {
            $this->addUserCriteria($qb);
        }

        return $qb;
    }

    /**
     * @param string|null $keyword
     * @param int         $maxResults
     *
     * @return Volunteer[]
     */
    public function searchAll(?string $keyword, int $maxResults) : array
    {
        return $this->searchAllQueryBuilder($keyword)
                    ->getQuery()
                    ->setMaxResults($maxResults)
                    ->getResult();
    }

    public function searchInStructureQueryBuilder(Structure $structure,
        ?string $keyword,
        bool $onlyEnabled = true,
        bool $onlyUsers = false) : QueryBuilder
    {
        $qb = $this->searchAllQueryBuilder($keyword, $onlyEnabled)
                   ->join('v.structures', 's')
                   ->andWhere('s.id = :structure')
                   ->setParameter('structure', $structure);

        if ($onlyUsers) {
            $this->addUserCriteria($qb);
        }

        return $qb;
    }

    public function searchAllQueryBuilder(?string $keyword, bool $enabled = false) : QueryBuilder
    {
        $qb = $this->createVolunteersQueryBuilder($enabled);

        if ($keyword) {
            $this->addSearchCriteria($qb, $keyword);
        }

        return $qb;
    }

    public function searchAllWithFiltersQueryBuilder(?string $criteria,
        bool $onlyEnabled,
        bool $onlyUsers) : QueryBuilder
    {
        $qb = $this->searchAllQueryBuilder($criteria, $onlyEnabled);

        if ($onlyUsers) {
            $this->addUserCriteria($qb);
        }

        return $qb;
    }

    public function foreach(callable $callback, bool $onlyEnabled = true)
    {
        $count = $this->createQueryBuilder('v')
                      ->select('COUNT(v.id)')
                      ->getQuery()
                      ->getSingleScalarResult();

        $offset = 0;
        $stop   = false;
        while ($offset < $count) {
            $qb = $this->createQueryBuilder('v');

            if ($onlyEnabled) {
                $qb->where('v.enabled = true');
            }

            $qb->setFirstResult($offset)
               ->setMaxResults(1000);

            $iterator = $qb->getQuery()->iterate();

            while (($row = $iterator->next()) !== false) {
                /* @var Volunteer $entity */
                $entity = reset($row);

                if (false === $return = $callback($entity)) {
                    $stop = true;
                    break;
                }

                if (true === $return) {
                    continue;
                }

                $this->_em->persist($entity);
                unset($entity);
            }

            $this->_em->flush();
            $this->_em->clear();

            if ($stop) {
                break;
            }

            $offset += 1000;
        }
    }

    public function getIssues(User $user) : array
    {
        $qb = $this->createAccessibleVolunteersQueryBuilder($user);

        return $qb
            ->leftJoin('v.phones', 'p')
            ->andWhere(
                $qb->expr()->orX(
                    'v.email IS NULL or v.email = \'\'',
                    'p.id is null'
                )
            )
            ->getQuery()
            ->getResult();
    }

    public function synchronizeWithPegass()
    {
        $qb = $this->createQueryBuilder('v');

        $sub = $this->_em->createQueryBuilder()
                         ->select('p.identifier')
                         ->from(Pegass::class, 'p')
                         ->where('p.type = :type')
                         ->andWhere('p.enabled = :enabled');

        $qb
            ->setParameter('type', Pegass::TYPE_VOLUNTEER)
            ->setParameter('enabled', false);

        $qb
            ->update()
            ->set('v.enabled', ':enabled')
            ->where($qb->expr()->in('v.identifier', $sub->getDQL()))
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $nivols
     *
     * @return Volunteer[]
     */
    public function getIdsByNivols(array $nivols) : array
    {
        return $this->createQueryBuilder('v')
                    ->select('v.id')
                    ->andWhere('v.nivol IN (:nivols)')
                    ->setParameter('nivols', $nivols, Connection::PARAM_STR_ARRAY)
                    ->getQuery()
                    ->getArrayResult();
    }

    public function filterInaccessibles(User $user, $volunteerIds) : array
    {
        $accessibles = $this->createAccessibleVolunteersQueryBuilder($user)
                            ->select('v.id')
                            ->andWhere('v.id IN (:volunteer_ids)')
                            ->setParameter('volunteer_ids', $volunteerIds)
                            ->getQuery()
                            ->getArrayResult();

        return array_diff($volunteerIds, array_column($accessibles, 'id'));
    }

    public function createAcessibleNivolsFilterQueryBuilder(array $nivols, User $user) : QueryBuilder
    {
        return $this->createAccessibleVolunteersQueryBuilder($user)
                    ->select('v.nivol')
                    ->andWhere("TRIM(LEADING '0' FROM v.nivol) IN (:nivols)")
                    ->setParameter('nivols', $nivols, Connection::PARAM_STR_ARRAY);
    }

    public function filterReachableNivols(array $nivols, User $user) : array
    {
        $valid = $this->createAcessibleNivolsFilterQueryBuilder($nivols, $user)
                      ->leftJoin('v.phones', 'p')
                      ->andWhere('p.id IS NOT NULL')
                      ->andWhere('v.phoneNumberOptin = true')
                      ->andWhere('v.email IS NOT NULL')
                      ->andWhere('v.emailOptin = true')
                      ->getQuery()
                      ->getArrayResult();

        return array_column($valid, 'nivol');
    }

    public function filterInvalidNivols(array $nivols) : array
    {
        $valid = $this->createVolunteersQueryBuilder(false)
                      ->select('v.nivol')
                      ->andWhere('v.nivol IN (:nivols)')
                      ->setParameter('nivols', $nivols)
                      ->getQuery()
                      ->getArrayResult();

        return array_diff($nivols, array_column($valid, 'nivol'));
    }

    public function getVolunteerList(array $volunteerIds, bool $onlyEnabled = true) : array
    {
        return $this
            ->createVolunteerListQueryBuilder($volunteerIds, $onlyEnabled)
            ->getQuery()
            ->getResult();
    }

    public function getVolunteerListForUser(User $user, array $volunteerIds) : array
    {
        return $this
            ->createAccessibleVolunteersQueryBuilder($user)
            ->andWhere('v.id IN (:volunteer_ids)')
            ->setParameter('volunteer_ids', $volunteerIds)
            ->getQuery()
            ->getResult();
    }

    public function getVolunteerListInStructuresQueryBuilder(array $structureIds) : QueryBuilder
    {
        return $this
            ->createQueryBuilder('v')
            ->select('DISTINCT v.id')
            ->where('v.enabled = true')
            ->join('v.structures', 's')
            ->andWhere('s.enabled = true')
            ->andWhere('s.id IN (:structure_ids)')
            ->setParameter('structure_ids', $structureIds);
    }

    public function getVolunteerListInStructures(array $structureIds) : array
    {
        return $this
            ->getVolunteerListInStructuresQueryBuilder($structureIds)
            ->getQuery()
            ->getArrayResult();
    }

    public function getVolunteerCountInStructures(array $structureIds) : int
    {
        return $this
            ->getVolunteerListInStructuresQueryBuilder($structureIds)
            ->select('COUNT(DISTINCT v.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getVolunteerListInStructuresHavingBadgesQueryBuilder(array $structureIds,
        array $badgeIds) : QueryBuilder
    {
        return $this
            ->createQueryBuilder('v')
            ->select('DISTINCT v.id')
            ->where('v.enabled = true')
            ->join('v.structures', 's')
            ->andWhere('s.enabled = true')
            ->andWhere('s.id IN (:structure_ids)')
            ->setParameter('structure_ids', $structureIds)
            ->join('v.badges', 'b')
            ->leftJoin('b.synonym', 'x')
            ->leftJoin('b.parent', 'p1')
            ->leftJoin('p1.parent', 'p2')
            ->leftJoin('p2.parent', 'p3')
            ->leftJoin('p3.parent', 'p4')
            ->andWhere('
                    b.id IN (:badge_ids) 
                 OR x.id IN (:badge_ids) 
                 OR p1.id IN (:badge_ids) 
                 OR p2.id IN (:badge_ids) 
                 OR p3.id IN (:badge_ids) 
                 OR p4.id IN (:badge_ids)
             ')
            ->setParameter('badge_ids', $badgeIds);
    }

    public function getVolunteerListInStructuresHavingBadges(array $structureIds, array $badgeIds) : array
    {
        return $this->getVolunteerListInStructuresHavingBadgesQueryBuilder($structureIds, $badgeIds)
                    ->getQuery()
                    ->getArrayResult();
    }

    public function getVolunteerCountInStructuresHavingBadges(array $structureIds, array $badgeIds) : int
    {
        return $this->getVolunteerListInStructuresHavingBadgesQueryBuilder($structureIds, $badgeIds)
                    ->select('COUNT(DISTINCT v.id)')
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    public function getVolunteerGlobalCounts(array $structureIds) : int
    {
        return $this
            ->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v)')
            ->join('v.structures', 's')
            ->andWhere('v.enabled = true')
            ->andWhere('s.enabled = true')
            ->andWhere('s.id IN (:structure_ids)')
            ->setParameter('structure_ids', $structureIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function filterDisabled(array $volunteerIds) : array
    {
        return $this
            ->createQueryBuilder('v')
            ->select('v.id')
            ->where('v.enabled = false')
            ->andWhere('v.id IN (:volunteer_ids)')
            ->setParameter('volunteer_ids', $volunteerIds)
            ->getQuery()
            ->getArrayResult();
    }

    public function filterPhoneLandline(array $volunteerIds) : array
    {
        return $this
            ->createVolunteerListQueryBuilder($volunteerIds)
            ->select('v.id')
            ->join('v.phones', 'p')
            ->andWhere('v.phoneNumberOptin = true')
            ->andWhere('p.preferred = true')
            ->andWhere('p.mobile = false')
            ->getQuery()
            ->getArrayResult();
    }

    public function filterPhoneMissing(array $volunteerIds) : array
    {
        return $this
            ->createVolunteerListQueryBuilder($volunteerIds)
            ->leftJoin('v.phones', 'p')
            ->andWhere('v.phoneNumberOptin = true')
            ->andWhere('p.id IS NULL')
            ->getQuery()
            ->getArrayResult();
    }

    public function filterPhoneOptout(array $volunteerIds) : array
    {
        return $this
            ->createVolunteerListQueryBuilder($volunteerIds)
            ->leftJoin('v.phones', 'p')
            ->andWhere('v.phoneNumberOptin = false')
            ->andWhere('p.preferred = true')
            ->getQuery()
            ->getArrayResult();
    }

    public function filterEmailMissing(array $volunteerIds) : array
    {
        return $this
            ->createVolunteerListQueryBuilder($volunteerIds)
            ->andWhere('v.email IS NULL')
            ->andWhere('v.emailOptin = true')
            ->getQuery()
            ->getArrayResult();
    }

    public function filterEmailOptout(array $volunteerIds) : array
    {
        return $this
            ->createVolunteerListQueryBuilder($volunteerIds)
            ->andWhere('v.email IS NOT NULL')
            ->andWhere('v.emailOptin = false')
            ->getQuery()
            ->getArrayResult();
    }

    private function createVolunteerListQueryBuilder(array $volunteerIds, bool $onlyEnabled = true) : QueryBuilder
    {
        return $this
            ->createVolunteersQueryBuilder($onlyEnabled)
            ->andWhere('v.id IN (:volunteer_ids)')
            ->setParameter('volunteer_ids', $volunteerIds);
    }

    private function createVolunteersQueryBuilder(bool $enabled = true) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
                   ->distinct();

        if ($enabled) {
            $qb->andWhere('v.enabled = true');
        }

        return $qb;
    }

    private function createAccessibleVolunteersQueryBuilder(User $user, bool $enabled = true) : QueryBuilder
    {
        return $this->createVolunteersQueryBuilder($enabled)
                    ->join('v.structures', 's')
                    ->join('s.users', 'u')
                    ->andWhere('u.id = :user')
                    ->setParameter('user', $user);
    }

    private function addSearchCriteria(QueryBuilder $qb, string $criteria)
    {
        $qb
            ->leftJoin('v.phones', 'p')
            ->andWhere(
                $qb->expr()->orX(
                    'v.nivol LIKE :criteria',
                    'v.firstName LIKE :criteria',
                    'v.lastName LIKE :criteria',
                    'p.e164 LIKE :criteria',
                    'p.national LIKE :criteria',
                    'p.international LIKE :criteria',
                    'v.email LIKE :criteria',
                    'CONCAT(v.firstName, \' \', v.lastName) LIKE :criteria',
                    'CONCAT(v.lastName, \' \', v.firstName) LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', ltrim($criteria, '0'))));
    }

    private function addUserCriteria(QueryBuilder $qb)
    {
        $qb
            ->join('v.user', 'vu');
    }
}
