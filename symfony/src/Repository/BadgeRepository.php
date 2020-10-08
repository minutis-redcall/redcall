<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Badge;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Badge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Badge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Badge[]    findAll()
 * @method Badge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BadgeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    public function getSearchInPublicBadgesQueryBuilder(?string $criteria) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.category', 'c')
                   ->where('b.restricted = false')
                   ->orderBy('c.priority', 'DESC')
                   ->addOrderBy('b.priority', 'DESC')
                   ->addOrderBy('b.name', 'ASC');

        if ($criteria) {
            $this->addSearchCriteria($qb, $criteria);
        }

        return $qb;
    }

    public function search(?string $criteria, int $limit) : array
    {
        return $this->getSearchInPublicBadgesQueryBuilder($criteria)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    private function addSearchCriteria(QueryBuilder $qb, string $criteria)
    {
        $qb->andWhere(
            $qb->expr()->orX(
                'b.name LIKE :criteria',
                'b.description LIKE :criteria',
                'c.name LIKE :criteria'
            )
        )
           ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
    }
}
