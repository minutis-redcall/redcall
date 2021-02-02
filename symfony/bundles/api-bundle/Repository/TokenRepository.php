<?php

namespace Bundles\ApiBundle\Repository;

use Bundles\ApiBundle\Entity\Token;
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

    public function getTokensForUserQueryBuilder(string $username)
    {
        return $this->createQueryBuilder('t')
            ->where('t.username = :username')
            ->setParameter('username', $username);
    }

    public function getTokensForUser(string $username) : array
    {
        return $this->getTokensForUserQueryBuilder($username)
                    ->getQuery()
                    ->getResult();
    }

    public function findTokenByNameForUser(string $username, string $name) : ?Token
    {
        return $this->getTokensForUserQueryBuilder($username)
                    ->andWhere('t.name = :name')
                    ->setParameter('name', $name)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }
}
