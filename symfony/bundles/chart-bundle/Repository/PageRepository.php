<?php

namespace Bundles\ChartBundle\Repository;

use Bundles\ChartBundle\Entity\StatPage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StatPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatPage[]    findAll()
 * @method StatPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatPage::class);
    }

    // /**
    //  * @return StatPage[] Returns an array of StatPage objects
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
    public function findOneBySomeField($value): ?StatPage
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
