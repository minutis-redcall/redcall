<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Volunteer;
use Doctrine\DBAL\Connection;
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

    public function findOneByExternalId(string $externalId) : ?Badge
    {
        return $this->findOneBy([
            'externalId' => $externalId,
        ]);
    }

    public function findOneByName(string $name) : ?Badge
    {
        return $this->findOneBy([
            'name' => $name,
        ]);
    }

    public function getSearchInBadgesQueryBuilder(?string $criteria,
        bool $onlyEnabled = true) : QueryBuilder
    {
        $qb = $this->getBadgesQueryBuilder();

        if ($criteria) {
            $this->addSearchCriteria($qb, $criteria);
        }

        if ($onlyEnabled) {
            $qb->andWhere('b.enabled = true');
        }

        return $qb;
    }

    public function getVolunteerCountInBadgeList(array $ids) : array
    {
        $rows = $this->createQueryBuilder('b')
                     ->select('b.id, COUNT(v) AS count')
                     ->join('b.volunteers', 'v')
                     ->andWhere('b.id IN (:ids)')
                     ->setParameter('ids', $ids)
                     ->groupBy('b.id')
                     ->getQuery()
                     ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['id']] = $row['count'];
        }

        return $counts;
    }

    public function searchForCompletion(?string $criteria, int $limit) : array
    {
        return $this->getSearchInBadgesQueryBuilder($criteria)
                    ->andWhere('b.synonym IS NULL')
                    ->andWhere('b.enabled = true')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function searchNonVisibleUsableBadge(?string $criteria, int $limit = 0) : array
    {
        return $this->getSearchInBadgesQueryBuilder($criteria)
                    ->andWhere('b.synonym IS NULL')
                    ->andWhere('b.visibility = false')
                    ->andWhere('b.enabled = true')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function getNonVisibleUsableBadgesList(array $ids)
    {
        return $this->getBadgesQueryBuilder()
                    ->andWhere('b.synonym IS NULL')
                    ->andWhere('b.visibility = false')
                    ->andWhere('b.enabled = true')
                    ->andWhere('b.id IN (:ids)')
                    ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                    ->getQuery()
                    ->getResult();
    }

    public function getPublicBadgesQueryBuilder() : QueryBuilder
    {
        return $this->getSearchInBadgesQueryBuilder(null)
                    ->andWhere('b.visibility = true')
                    ->andWhere('b.enabled = true');
    }

    public function getBadgesInCategoryQueryBuilder(Category $category) : QueryBuilder
    {
        return $this->createQueryBuilder('b')
                    ->andWhere('b.enabled = true')
                    ->andWhere('b.visibility = true')
                    ->andWhere('b.synonym IS NULL')
                    ->join('b.category', 'c')
                    ->andWhere('c.id = :category')
                    ->setParameter('category', $category);
    }

    public function searchForVolunteerQueryBuilder(Volunteer $volunteer,
        ?string $criteria) : QueryBuilder
    {
        return $this->getSearchInBadgesQueryBuilder($criteria)
                    ->join('b.volunteers', 'v')
                    ->andWhere('v.id = :volunteer')
                    ->setParameter('volunteer', $volunteer);
    }

    private function getBadgesQueryBuilder() : QueryBuilder
    {
        return $this
            ->createQueryBuilder('b')
            ->leftJoin('b.category', 'c')
            ->addOrderBy('b.visibility', 'DESC')
            ->leftJoin('b.synonym', 's')
            ->addOrderBy('s.id', 'ASC')
            ->addOrderBy('(1000 - c.priority) * 1000 + 1000 - b.renderingPriority', 'DESC')
            ->addOrderBy('b.name', 'ASC')
            ->groupBy('b.id');
    }

    private function addSearchCriteria(QueryBuilder $qb, string $criteria)
    {
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    'b.name LIKE :criteria',
                    'b.description LIKE :criteria',
                    'b.externalId LIKE :criteria',
                    'c.name LIKE :criteria',
                    'c.externalId LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
    }
}
