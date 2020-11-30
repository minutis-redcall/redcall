<?php

namespace App\Repository;

use App\Entity\User;
use Bundles\PasswordLoginBundle\Repository\AbstractUserRepository;
use Bundles\PasswordLoginBundle\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends AbstractUserRepository implements UserRepositoryInterface
{
    /**
     * @param RegistryInterface $registry
     */
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param string $criteria
     *
     * @return QueryBuilder
     */
    public function searchQueryBuilder(?string $criteria) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->leftJoin('u.volunteer', 'v')
            ->leftJoin('v.phones', 'p')
            ->where(
                $qb->expr()->orX(
                    'u.username LIKE :criteria',
                    'v.nivol LIKE :criteria',
                    'v.firstName LIKE :criteria',
                    'v.lastName LIKE :criteria',
                    'v.email LIKE :criteria',
                    'p.e164 LIKE :criteria',
                    'p.national LIKE :criteria',
                    'p.international LIKE :criteria',
                    'CONCAT(v.firstName, \' \', v.lastName) LIKE :criteria',
                    'CONCAT(v.lastName, \' \', v.firstName) LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', $criteria))
            ->addOrderBy('u.registeredAt', 'DESC')
            ->addOrderBy('u.username', 'ASC');
    }
}

