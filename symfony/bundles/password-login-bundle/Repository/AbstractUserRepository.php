<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractUserRepository extends ServiceEntityRepository
{
    public function findOneByUsername(string $username) : ?AbstractUser
    {
        return parent::findOneByUsername($username);
    }

    public function searchAll(?string $criteria) : array
    {
        return $this->searchAllQueryBuilder($criteria)
                    ->getQuery()
                    ->getResult();
    }

    private function searchAllQueryBuilder(?string $criteria) : QueryBuilder
    {
        return $this->createQueryBuilder('u')
                    ->where('u.username LIKE :criteria')
                    ->setParameter('criteria', sprintf('%%%s%%', $criteria))
                    ->addOrderBy('u.registeredAt', 'DESC')
                    ->addOrderBy('u.username', 'ASC');
    }
}
