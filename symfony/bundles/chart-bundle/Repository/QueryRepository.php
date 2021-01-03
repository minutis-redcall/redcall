<?php

namespace Bundles\ChartBundle\Repository;

use Bundles\ChartBundle\Entity\StatQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StatQuery|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatQuery|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatQuery[]    findAll()
 * @method StatQuery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatQuery::class);
    }

    // /**
    //  * @return StatQuery[] Returns an array of StatQuery objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StatQuery
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
