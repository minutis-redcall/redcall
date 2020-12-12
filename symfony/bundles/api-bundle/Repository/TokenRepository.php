<?php

namespace Bundles\ApiBundle\Repository;

use Bundles\ApiBundle\Entity\Token;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Token|null find($id, $lockMode = null, $lockVersion = null)
 * @method Token|null findOneBy(array $criteria, array $orderBy = null)
 * @method Token[]    findAll()
 * @method Token[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function getTokensQueryBuilderForUser(string $username) : QueryBuilder
    {
        return $this->createQueryBuilder('t')
                    ->where('t.username = :username')
                    ->setParameter('username', $username);
    }

    public function findTokenByNameForUser(string $username, string $name) : ?Token
    {
        return $this->getTokensQueryBuilderForUser($username)
                    ->andWhere('t.name = :name')
                    ->setParameter('name', $name)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }
}
