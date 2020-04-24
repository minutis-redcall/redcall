<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Entity\Volunteer;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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

    private function createVolunteersQueryBuilder(bool $enabled = true): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->distinct();

        if ($enabled) {
            $qb->andWhere('v.enabled = true');
        }

        return $qb;
    }

    private function createAccessibleVolunteersQueryBuilder(UserInformation $user, bool $enabled = true): QueryBuilder
    {
        return $this->createVolunteersQueryBuilder($enabled)
            ->join('v.structures', 's')
            ->join('s.users', 'u')
            ->andWhere('u.id = :user')
            ->setParameter('user', $user);
    }

    /**
     * @param Volunteer $volunteer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function disable(Volunteer $volunteer)
    {
        if ($volunteer->isEnabled()) {
            $volunteer->setEnabled(false);
            $this->save($volunteer);
        }
    }

    /**
     * @param Volunteer $volunteer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function enable(Volunteer $volunteer)
    {
        if (!$volunteer->isEnabled()) {
            $volunteer->setEnabled(true);
            $this->save($volunteer);
        }
    }

    /**
     * @param Volunteer $volunteer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function lock(Volunteer $volunteer)
    {
        if (!$volunteer->isLocked()) {
            $volunteer->setLocked(true);
            $this->save($volunteer);
        }
    }

    /**
     * @param Volunteer $volunteer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function unlock(Volunteer $volunteer)
    {
        if ($volunteer->isLocked()) {
            $volunteer->setLocked(false);
            $this->save($volunteer);
        }
    }

    /**
     * @param $nivol
     *
     * @return Volunteer|null
     */
    public function findOneByNivol($nivol): ?Volunteer
    {
        return $this->findOneBy([
            'nivol' => ltrim($nivol, '0'),
        ]);
    }

    /**
     * @param string          $keyword
     * @param int             $maxResults
     * @param UserInformation $user
     *
     * @return Volunteer[]
     */
    public function searchForUser(UserInformation $user, ?string $keyword, int $maxResults): array
    {
        return $this->searchForUserQueryBuilder($user, $keyword)
                    ->getQuery()
                    ->setMaxResults($maxResults)
                    ->getResult();
    }

    public function searchForUserQueryBuilder(UserInformation $user, ?string $keyword): QueryBuilder
    {
        $qb = $this->createAccessibleVolunteersQueryBuilder($user, false);

        if ($keyword) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'v.nivol LIKE :keyword',
                        'v.firstName LIKE :keyword',
                        'v.lastName LIKE :keyword',
                        'v.phoneNumber LIKE :keyword',
                        'v.email LIKE :keyword',
                        'CONCAT(v.firstName, \' \', v.lastName) LIKE :keyword',
                        'CONCAT(v.lastName, \' \', v.firstName) LIKE :keyword'
                    )
                )
                ->setParameter('keyword', sprintf('%%%s%%', $keyword));
        }

        return $qb;
    }

    /**
     * @param string|null $keyword
     * @param int         $maxResults
     *
     * @return Volunteer[]
     */
    public function searchAll(?string $keyword, int $maxResults): array
    {
        return $this->searchAllQueryBuilder($keyword)
                    ->getQuery()
                    ->setMaxResults($maxResults)
                    ->getResult();
    }

    public function searchInStructureQueryBuilder(Structure $structure, ?string $keyword): QueryBuilder
    {
        return $this->searchAllQueryBuilder($keyword)
            ->join('v.structures', 's')
            ->where('s.id = :structure')
            ->setParameter('structure', $structure);
    }

    public function searchAllQueryBuilder(?string $keyword): QueryBuilder
    {
        $qb = $this->createVolunteersQueryBuilder(false);

        if ($keyword) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'v.nivol LIKE :keyword',
                        'v.firstName LIKE :keyword',
                        'v.lastName LIKE :keyword',
                        'v.phoneNumber LIKE :keyword',
                        'v.email LIKE :keyword',
                        'CONCAT(v.firstName, \' \', v.lastName) LIKE :keyword',
                        'CONCAT(v.lastName, \' \', v.firstName) LIKE :keyword'
                    )
                )
                ->setParameter('keyword', sprintf('%%%s%%', $keyword));
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
                    break;
                }

                if (true === $return) {
                    continue;
                }

                $this->_em->persist($entity);
            }

            $this->_em->flush();
            $this->_em->clear();

            $offset += 1000;
        }
    }

    public function getIssues(UserInformation $user): array
    {
        $qb = $this->createAccessibleVolunteersQueryBuilder($user);

        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    'v.email IS NULL or v.email = \'\'',
                    'v.phoneNumber IS NULL or v.phoneNumber = \'\''
                )
            )
            ->getQuery()
            ->getResult();
    }

    public function synchronizeWithPegass()
    {
        foreach ([false, true] as $enabled) {
            $qb = $this->createQueryBuilder('v');

            $sub = $this->_em->createQueryBuilder()
                ->select('p.identifier')
                ->from(Pegass::class, 'p')
                ->where('p.type = :type')
                ->andWhere('p.enabled = :enabled');

            $qb
                ->setParameter('type', Pegass::TYPE_VOLUNTEER)
                ->setParameter('enabled', $enabled);

            $qb
                ->update()
                ->set('v.enabled', ':enabled')
                ->where($qb->expr()->in('v.identifier', $sub->getDQL()))
                ->getQuery()
                ->execute();
        }
    }

    /**
     * @param array $nivols
     *
     * @return Volunteer[]
     */
    public function filterByNivols(array $nivols): array
    {
        return $this->createVolunteersQueryBuilder()
            ->andWhere('v.nivol IN (:nivols)')
            ->setParameter('nivols', $nivols, Connection::PARAM_STR_ARRAY)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array           $nivols
     * @param UserInformation $user
     *
     * @return Volunteer[]
     */
    public function filterByNivolsAndAccess(array $nivols, UserInformation $user): array
    {
        return $this->createAccessibleVolunteersQueryBuilder($user)
            ->andWhere('v.nivol IN (:nivols)')
            ->setParameter('nivols', $nivols, Connection::PARAM_STR_ARRAY)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $nivols
     *
     * @return Volunteer[]
     */
    public function filterByIds(array $ids): array
    {
        return $this->createVolunteersQueryBuilder()
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array           $ids
     * @param UserInformation $user
     *
     * @return Volunteer[]
     */
    public function filterByIdsAndAccess(array $ids, UserInformation $user): array
    {
        return $this->createAccessibleVolunteersQueryBuilder($user)
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult();
    }

    public function filterInvalidNivols(array $nivols): array
    {
        $valid = $this->createVolunteersQueryBuilder(false)
            ->select('v.nivol')
            ->andWhere('v.nivol IN (:nivols)')
            ->setParameter('nivols', $nivols)
            ->getQuery()
            ->getArrayResult();

        return array_diff($nivols, array_column($valid, 'nivol'));
    }

    public function filterDisabledNivols(array $nivols): array
    {
        $disabled = $this->createVolunteersQueryBuilder(false)
            ->select('v.nivol')
            ->andWhere('v.nivol IN (:nivols)')
            ->setParameter('nivols', $nivols)
            ->andWhere('v.enabled = false')
            ->getQuery()
            ->getArrayResult();

        return array_column($disabled, 'nivol');
    }
}
