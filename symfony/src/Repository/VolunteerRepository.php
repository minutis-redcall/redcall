<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\UserInformation;
use App\Entity\Volunteer;
use Bundles\PasswordLoginBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Mapping\MappingException;
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

    /**
     * @param array $volunteerIds
     *
     * @return Volunteer[]
     */
    public function findByIds(array $volunteerIds)
    {
        return $this
            ->createQueryBuilder('v')
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $volunteerIds)
            ->getQuery()
            ->getResult();
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
     * @return array
     */
    public function listVolunteerNivols(): array
    {
        $rows = $this->createQueryBuilder('v')
                     ->select('v.nivol')
                     ->getQuery()
                     ->getArrayResult();

        return array_column($rows, 'nivol');
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

    /**
     * @param UserInformation $user
     * @param string|null     $keyword
     *
     * @return QueryBuilder
     */
    public function searchForUserQueryBuilder(UserInformation $user, ?string $keyword): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v');

        $qb
            ->join('v.structures', 's')
            ->join('s.users', 'u')
            ->where('v.enabled = true')
            ->andWhere('u.id = :user')
            ->setParameter('user', $user);

        if ($keyword) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'v.nivol LIKE :keyword',
                        'v.firstName LIKE :keyword',
                        'v.lastName LIKE :keyword',
                        'v.phoneNumber LIKE :keyword',
                        'v.email LIKE :keyword'
                    )
                )
                ->setParameter('keyword', sprintf('%%%s%%', $keyword));
        }

        return $qb;
    }

    /**
     * @param string $keyword
     * @param int    $maxResults
     * @param User   $user
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

    /**
     * @param string $keyword
     *
     * @return QueryBuilder
     */
    public function searchAllQueryBuilder(?string $keyword): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v');

        if ($keyword) {
            $qb
                ->where(
                    $qb->expr()->orX(
                        'v.nivol LIKE :keyword',
                        'v.firstName LIKE :keyword',
                        'v.lastName LIKE :keyword',
                        'v.phoneNumber LIKE :keyword',
                        'v.email LIKE :keyword'
                    )
                )
                ->setParameter('keyword', sprintf('%%%s%%', $keyword));
        }

        return $qb;
    }

    public function expireAll()
    {
        $this->createQueryBuilder('v')
             ->update()
             ->set('v.lastPegassUpdate', ':expiredDate')
             ->setParameter('expiredDate', new DateTime('1984-07-10'))
             ->getQuery()
             ->execute();
    }

    /**
     * @param callable $callback
     * @param bool     $onlyEnabled
     *
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
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
}
