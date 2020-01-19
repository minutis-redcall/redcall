<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Tag;
use App\Entity\Volunteer;
use Bundles\PasswordLoginBundle\Entity\User;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Volunteer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Volunteer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Volunteer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerRepository extends BaseRepository
{
    public function __construct(RegistryInterface $registry)
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
     * @param int $limit
     *
     * @return array
     *
     * @throws \Exception
     */
    public function findVolunteersToRefresh(int $limit): array
    {
        return $this
            ->createQueryBuilder('v')
            ->andWhere('v.enabled = true')
            ->andWhere('v.locked = false')
            ->andWhere('v.lastPegassUpdate < :lastMonth')
            ->setParameter('lastMonth', new \DateTime('last month'))
            ->orderBy('v.lastPegassUpdate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


    /**
     * @param Volunteer $volunteer
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @param string    $keyword
     * @param int       $maxResults
     * @param User|null $user
     *
     * @return array
     */
    public function search(string $keyword, int $maxResults = 20, User $user = null): array
    {
        if ($user) {
            return $this->searchForUser($keyword, $maxResults, $user);
        }

        return $this->searchAll($keyword, $maxResults);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getVolunteersCountByTagsForUser(User $user): array
    {
        $rows = $this->_em->createQueryBuilder('t')
                          ->select('t.id, COUNT(v.id) AS c')
                          ->from(Tag::class, 't')
                          ->innerJoin('t.volunteers', 'v')
                          ->innerJoin('v.structures', 's')
                          ->innerJoin('s.users', 'u')
                          ->where('u.user = :user')
                          ->setParameter('user', $user)
                          ->andWhere('v.enabled = true')
                          ->groupBy('t.id')
                          ->getQuery()
                          ->getArrayResult();

        $tagCounts = [];
        foreach ($rows as $row) {
            $tagCounts[$row['id']] = $row['c'];
        }

        return $tagCounts;
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function findCallableForUser(User $user): array
    {
        return $this->createQueryBuilder('v')
                    ->innerJoin('v.structures', 's')
                    ->innerJoin('s.users', 'u')
                    ->where('u.user = :user')
                    ->setParameter('user', $user)
                    ->andWhere('v.enabled = true')
                    ->orderBy('v.firstName', 'ASC')
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @param string $keyword
     * @param int    $maxResults
     * @param User   $user
     */
    private function searchForUser(string $keyword, int $maxResults, User $user)
    {
        $qb = $this->createQueryBuilder('v');

        return $qb
            ->innerJoin('v.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.user = :user')
            ->setParameter('user', $user)
            ->andWhere(
                $qb->expr()->orX(
                    'v.nivol LIKE :keyword',
                    'v.firstName LIKE :keyword',
                    'v.lastName LIKE :keyword'
                )
            )
            ->setParameter('keyword', sprintf('%%%s%%', $keyword))
            ->getQuery()
            ->setMaxResults($maxResults)
            ->getResult();
    }

    /**
     * @param string $keyword
     * @param int    $maxResults
     * @param User   $user
     *
     * @return array
     */
    private function searchAll(string $keyword, int $maxResults): array
    {
        return $this->createQueryBuilder('v')
                    ->where('v.nivol LIKE :keyword')
                    ->orWhere('v.firstName LIKE :keyword')
                    ->orWhere('v.lastName LIKE :keyword')
                    ->setParameter('keyword', sprintf('%%%s%%', $keyword))
                    ->getQuery()
                    ->setMaxResults($maxResults)
                    ->getResult();
    }

}
