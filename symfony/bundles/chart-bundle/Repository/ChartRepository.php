<?php

namespace Bundles\ChartBundle\Repository;

use Bundles\ChartBundle\Entity\StatChart;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StatChart|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatChart|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatChart[]    findAll()
 * @method StatChart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChartRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatChart::class);
    }

    // /**
    //  * @return StatChart[] Returns an array of StatChart objects
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
    public function findOneBySomeField($value): ?StatChart
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
