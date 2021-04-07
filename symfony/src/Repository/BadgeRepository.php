<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Badge;
use App\Entity\Category;
use App\Security\Helper\Security;
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
    /**
     * @var Security
     */
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        $this->security = $security;

        parent::__construct($registry, Badge::class);
    }

    public function findOneByExternalId(string $platform, string $externalId) : ?Badge
    {
        return $this->findOneBy([
            'platform'   => $platform,
            'externalId' => $externalId,
        ]);
    }

    public function findOneByExternalIdAndCurrentPlatform(string $externalId) : ?Badge
    {
        return $this->findOneBy([
            'platform'   => $this->security->getPlatform(),
            'externalId' => $externalId,
        ]);
    }

    public function getSearchInBadgesQueryBuilder(string $platform,
        ?string $criteria,
        bool $onlyEnabled = true) : QueryBuilder
    {
        $qb = $this->getBadgesQueryBuilder()
                   ->andWhere('b.platform = :platform')
                   ->setParameter('platform', $platform);

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

    public function searchForCompletion(string $platform, ?string $criteria, int $limit) : array
    {
        return $this->getSearchInBadgesQueryBuilder($platform, $criteria)
                    ->andWhere('b.synonym IS NULL')
                    ->andWhere('b.enabled = true')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function searchNonVisibleUsableBadge(string $platform, ?string $criteria, int $limit = 0) : array
    {
        return $this->getSearchInBadgesQueryBuilder($platform, $criteria)
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

    public function getPublicBadgesQueryBuilder(string $platform) : QueryBuilder
    {
        return $this->getSearchInBadgesQueryBuilder($platform, null)
                    ->andWhere('b.visibility = true')
                    ->andWhere('b.enabled = true');
    }

    public function getBadgesInCategoryQueryBuilder(string $platform, Category $category) : QueryBuilder
    {
        return $this->createQueryBuilder('b')
                    ->andWhere('b.platform = :platform')
                    ->setParameter('platform', $platform)
                    ->andWhere('b.enabled = true')
                    ->andWhere('b.visibility = true')
                    ->andWhere('b.synonym IS NULL')
                    ->join('b.category', 'c')
                    ->andWhere('c.id = :category')
                    ->setParameter('category', $category);
    }

    private function getBadgesQueryBuilder() : QueryBuilder
    {
        return $this
            ->createQueryBuilder('b')
            ->leftJoin('b.category', 'c')
            ->addOrderBy('b.visibility', 'DESC')
            ->addOrderBy('b.synonym', 'ASC')
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
                    'c.name LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
    }
}
