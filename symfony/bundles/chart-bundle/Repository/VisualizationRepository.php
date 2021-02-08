<?php

namespace Bundles\ChartBundle\Repository;

use Bundles\ChartBundle\Entity\StatVisualization;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StatVisualization|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatVisualization|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatVisualization[]    findAll()
 * @method StatVisualization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChartRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatVisualization::class);
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
