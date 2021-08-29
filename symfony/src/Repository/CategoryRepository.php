<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Category;
use App\Security\Helper\Security;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends BaseRepository
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        $this->security = $security;

        parent::__construct($registry, Category::class);
    }

    public function findOneByExternalId(string $platform, string $externalId) : ?Category
    {
        return $this->findOneBy([
            'platform'   => $platform,
            'externalId' => $externalId,
        ]);
    }

    public function findOneByExternalIdAndCurrentPlatform(string $externalId) : ?Category
    {
        return $this->findOneBy([
            'platform'   => $this->security->getPlatform(),
            'externalId' => $externalId,
        ]);
    }

    public function getSearchInCategoriesQueryBuilder(string $platform, ?string $criteria) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
                   ->andWhere('c.platform = :platform')
                   ->setParameter('platform', $platform)
                   ->addOrderBy('c.enabled', 'DESC')
                   ->addOrderBy('c.priority', 'ASC');

        if ($criteria) {
            $this->addSearchCriteria($qb, $criteria);
        }

        return $qb;
    }

    public function search(string $platform, ?string $criteria, int $limit) : array
    {
        return $this->getSearchInCategoriesQueryBuilder($platform, $criteria)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    private function addSearchCriteria(QueryBuilder $qb, string $criteria)
    {
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    'c.name LIKE :criteria',
                    'c.externalId LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
    }
}
