<?php

namespace Bundles\ApiBundle\Repository;

use Bundles\ApiBundle\Entity\TokenLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TokenLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenLog[]    findAll()
 * @method TokenLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenLog::class);
    }

    // /**
    //  * @return TokenLog[] Returns an array of TokenLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TokenLog
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
