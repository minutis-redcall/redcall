<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class UserRepository extends EntityRepository
{
    public function searchAll(?string $criteria)
    {
        return $this->searchAllQueryBuilder($criteria)
                    ->getQuery()
                    ->getResult();
    }

    public function searchAllQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->createQueryBuilder('u')
                    ->where('u.username LIKE :criteria')
                    ->setParameter('criteria', sprintf('%%%s%%', $criteria))
                    ->addOrderBy('u.registeredAt', 'DESC')
                    ->addOrderBy('u.username', 'ASC');
    }

    public function save(User $user)
    {
        $this->_em->persist($user);
        $this->_em->flush($user);
    }
}
