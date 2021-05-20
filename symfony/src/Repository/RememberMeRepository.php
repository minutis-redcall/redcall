<?php

namespace App\Repository;

use App\Entity\RememberMe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RememberMe|null find($id, $lockMode = null, $lockVersion = null)
 * @method RememberMe|null findOneBy(array $criteria, array $orderBy = null)
 * @method RememberMe[]    findAll()
 * @method RememberMe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RememberMeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RememberMe::class);
    }

    // /**
    //  * @return RememberMe[] Returns an array of RememberMe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RememberMe
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
